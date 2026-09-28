<?php

namespace App\Services\Platform;

use App\Contracts\UserRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Models\Audit;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use Database\Seeders\PlatformUserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Gestiona la billetera de la plataforma (usuario sistema playrke_platform).
 *
 * Responsabilidades:
 * - Resolver la wallet de plataforma por moneda (con caché corto)
 * - Acreditar comisiones RKE en tiempo real
 * - Exponer saldo + movimientos para el dashboard del superadmin
 */
final class PlatformWalletService
{
    private const CACHE_TTL = 300; // 5 min

    public function __construct(
        private readonly WalletRepositoryInterface         $walletRepository,
        private readonly WalletMovementRepositoryInterface $movementRepository,
    ) {}

    // -------------------------------------------------------------------------
    // Acreditación de comisión RKE
    // -------------------------------------------------------------------------

    /**
     * Acredita una comisión RKE a la wallet de plataforma en la moneda indicada.
     * Se ejecuta dentro de la transacción de venta de cartones.
     *
     * DB::transaction() propia: si el INSERT del movimiento fallara después del
     * increment() (deadlock, columna truncada, etc.), sin esto el balance de la
     * wallet RKE quedaría incrementado sin su WalletMovement — el caller
     * (RoomMetricsService) envuelve esta llamada en try/catch para no
     * interrumpir la venta, así que ese catch silenciaría justo el escenario
     * que rompe la cuadratura contable (balance != suma de movimientos).
     *
     * @param  string $currencyId  ID de la moneda de la venta
     * @param  float  $amount      Monto de la comisión en la moneda indicada por $currencyId
     * @param  string $description Referencia (ej: "RKE – Sala #abc123")
     */
    public function creditRke(string $currencyId, float $amount, string $description = 'Comisión RKE'): void
    {
        // amount == 0 es legítimo (sala con rke_commission configurada al
        // 0%) y no debe interrumpir la venta ni generar ruido. amount < 0
        // sí es anómalo (bug de cálculo de tasa/redondeo aguas arriba en
        // RoomMetricsService) — antes ambos casos volvían en silencio sin
        // distinción, perdiendo la señal de que algo calculó mal.
        if ($amount < 0) {
            \Illuminate\Support\Facades\Log::warning('[PlatformWalletService] creditRke() recibió un monto negativo — se ignora sin acreditar', [
                'currency_id' => $currencyId,
                'amount'      => $amount,
                'description' => $description,
            ]);
        }

        if ($amount <= 0) {
            return;
        }

        // ensureWallet() (no resolveWallet()): la primera venta con comisión
        // RKE en una moneda que la plataforma nunca vendió antes no debe
        // perder esa comisión — antes, si la wallet no existía todavía,
        // resolveWallet() (solo búsqueda, nunca crea) devolvía null y la
        // comisión se descartaba en silencio sin loguear ni acreditar nada,
        // pese al comentario "no bloqueamos la venta" sugiriendo que era un
        // caso tolerado — en realidad era dinero de la plataforma perdido
        // sin rastro.
        $wallet = $this->ensureWallet($currencyId);

        if (! $wallet) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $description) {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $locked->balance;

            $this->walletRepository->incrementBalance((string) $locked->id, $amount);

            $this->movementRepository->record([
                'wallet_id'      => (string) $locked->id,
                'user_id'        => (string) $locked->user_id,
                'type'           => WalletMovement::TYPE_RKE_COMMISSION,
                'status'         => WalletMovement::STATUS_COMPLETED,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $amount,
                'description'    => $description,
            ]);
        });

        // Invalidar caché de saldo
        Cache::forget($this->cacheKey($currencyId));
    }

    /**
     * Revierte una comisión RKE acreditada por una venta que luego se reembolsó
     * (sala cancelada o pago rechazado — ver BingoLotSaleReversalService).
     * Sin esto la plataforma se quedaba con el RKE de un dinero que el jugador
     * recuperó al 100%.
     *
     * Idempotente por $idempotencyKey (el job de reembolso puede reintentarse).
     * Si la wallet no tiene saldo suficiente (ya se retiró), NO lanza — el
     * reembolso al jugador es prioritario —: loguea el faltante como error para
     * conciliación manual y devuelve null.
     */
    public function reverseRke(string $currencyId, float $amount, string $idempotencyKey, string $description = 'Reversión comisión RKE'): ?WalletMovement
    {
        if ($amount <= 0) {
            return null;
        }

        $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $wallet = $this->resolveWallet($currencyId);
        if (! $wallet) {
            \Illuminate\Support\Facades\Log::error('[PlatformWalletService] reverseRke(): no existe wallet de plataforma para la moneda', [
                'currency_id' => $currencyId, 'amount' => $amount, 'idempotency_key' => $idempotencyKey,
            ]);
            return null;
        }

        $movement = \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $description, $idempotencyKey, $currencyId) {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $locked->balance;

            if ($balanceBefore < $amount) {
                \Illuminate\Support\Facades\Log::error('[PlatformWalletService] reverseRke(): saldo insuficiente para revertir RKE — conciliar manualmente', [
                    'currency_id' => $currencyId, 'amount' => $amount, 'balance' => $balanceBefore, 'idempotency_key' => $idempotencyKey,
                ]);
                return null;
            }

            $this->walletRepository->decrementBalance((string) $locked->id, $amount);

            return $this->movementRepository->record([
                'wallet_id'       => (string) $locked->id,
                'user_id'         => (string) $locked->user_id,
                'type'            => WalletMovement::TYPE_RKE_COMMISSION_REVERSAL,
                'status'          => WalletMovement::STATUS_COMPLETED,
                'amount'          => $amount,
                'balance_before'  => $balanceBefore,
                'balance_after'   => $balanceBefore - $amount,
                'description'     => $description,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        Cache::forget($this->cacheKey($currencyId));

        return $movement;
    }

    /**
     * Acredita una comisión de marketplace a la wallet de plataforma, de
     * forma idempotente — a diferencia de creditRke() (llamada inline desde
     * una única venta de cartones, sin reintentos), el settlement de un
     * Escrow (ver App\Domains\Marketplace\Escrow\Services\EscrowService)
     * puede reintentarse tras un fallo parcial, así que necesita el mismo
     * patrón idempotency_key que WalletService::recordPrize().
     */
    public function creditMarketplaceFee(string $currencyId, float $amount, string $idempotencyKey, string $description = 'Comisión de marketplace'): ?WalletMovement
    {
        if ($amount <= 0) {
            return null;
        }

        $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $wallet = $this->ensureWallet($currencyId);
        if (! $wallet) {
            return null;
        }

        try {
            $movement = \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $description, $idempotencyKey) {
                $balanceBefore = (float) $wallet->balance;

                $this->walletRepository->incrementBalance((string) $wallet->id, $amount);
                $wallet->refresh();

                return $this->movementRepository->record([
                    'wallet_id'       => (string) $wallet->id,
                    'user_id'         => (string) $wallet->user_id,
                    'type'            => WalletMovement::TYPE_MARKETPLACE_FEE,
                    'status'          => WalletMovement::STATUS_COMPLETED,
                    'amount'          => $amount,
                    'balance_before'  => $balanceBefore,
                    'balance_after'   => (float) $wallet->balance,
                    'description'     => $description,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Misma carrera que WalletService::recordPrize(): otro proceso ya
            // acreditó esta clave entre el SELECT de arriba y este INSERT.
            if (str_contains($e->getMessage(), 'idempotency_key')) {
                $existing = WalletMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        }

        Cache::forget($this->cacheKey($currencyId));

        return $movement;
    }

    // -------------------------------------------------------------------------
    // Gestión de wallets (depósito / retiro / auto-creación)
    // -------------------------------------------------------------------------

    /**
     * Garantiza que exista una wallet de plataforma para la moneda indicada.
     * Si no existe, la crea. Útil cuando se activa una nueva moneda en el sistema.
     */
    public function ensureWallet(string $currencyId): ?Wallet
    {
        $user = $this->resolvePlatformUser();
        if (! $user) {
            return null;
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency_id' => $currencyId],
            ['balance' => 0.00, 'status' => 'ACTIVE']
        );

        Cache::forget($this->cacheKey($currencyId));

        return $wallet;
    }

    /**
     * Registra un depósito manual en la wallet de plataforma (ej. recarga de caja).
     *
     * @throws \RuntimeException si la wallet no existe para la moneda dada
     */
    public function deposit(string $currencyId, float $amount, string $description = 'Depósito manual'): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto del depósito debe ser mayor a cero.');
        }

        $wallet = $this->ensureWallet($currencyId);

        if (! $wallet) {
            throw new \RuntimeException("No se encontró wallet de plataforma para la moneda {$currencyId}.");
        }

        [$balanceBefore, $balanceAfter] = \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount, $description) {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $locked->balance;

            $this->walletRepository->incrementBalance((string) $locked->id, $amount);

            $this->movementRepository->record([
                'wallet_id'      => (string) $locked->id,
                'user_id'        => (string) $locked->user_id,
                'type'           => WalletMovement::TYPE_DEPOSIT,
                'status'         => WalletMovement::STATUS_COMPLETED,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $amount,
                'description'    => $description,
            ]);

            return [$balanceBefore, $balanceBefore + $amount];
        });

        $wallet->refresh();

        Cache::forget($this->cacheKey($currencyId));

        $this->logCashAudit('deposit', $wallet, $amount, $balanceBefore, $balanceAfter, $description);
    }

    /**
     * Registra un retiro manual de la wallet de plataforma (ej. extracción de fondos).
     *
     * Bajo lockForUpdate() + DB::transaction(): a diferencia de creditRke()/deposit()
     * (que solo suman y nunca pueden dejar el balance negativo), un retiro sí necesita
     * revalidar el saldo bajo lock — sin esto, dos retiros concurrentes podrían leer el
     * mismo balance, pasar ambos la validación y dejar la wallet en negativo (el CHECK
     * de BD lo evitaría, pero como una excepción SQL cruda en vez de un error de negocio).
     *
     * @throws \RuntimeException si la wallet no existe o fondos insuficientes
     */
    public function withdraw(string $currencyId, float $amount, string $description = 'Retiro manual'): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto del retiro debe ser mayor a cero.');
        }

        $wallet = $this->resolveWallet($currencyId);

        if (! $wallet) {
            throw new \RuntimeException("No se encontró wallet de plataforma para la moneda {$currencyId}.");
        }

        [$balanceBefore, $balanceAfter] = \Illuminate\Support\Facades\DB::transaction(function () use ($wallet, $amount) {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->balance < $amount) {
                throw new \RuntimeException('Fondos insuficientes en la wallet de plataforma.');
            }

            $balanceBefore = (float) $locked->balance;

            $this->walletRepository->decrementBalance((string) $locked->id, $amount);

            return [$balanceBefore, $balanceBefore - $amount];
        });

        $wallet->refresh();

        $this->movementRepository->record([
            'wallet_id'      => (string) $wallet->id,
            'user_id'        => (string) $wallet->user_id,
            'type'           => WalletMovement::TYPE_WITHDRAWAL,
            'status'         => WalletMovement::STATUS_COMPLETED,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'description'    => $description,
        ]);

        Cache::forget($this->cacheKey($currencyId));

        $this->logCashAudit('withdraw', $wallet, $amount, $balanceBefore, $balanceAfter, $description);
    }

    // -------------------------------------------------------------------------
    // Consultas para el dashboard
    // -------------------------------------------------------------------------

    /**
     * Devuelve todas las wallets de la plataforma con su saldo actual.
     *
     * @return array<int, array{id: string, currency_id: string, currency_code: string, currency_symbol: string, balance: float, status: string}>
     */
    public function getWallets(): array
    {
        $user = $this->resolvePlatformUser();
        if (! $user) {
            return [];
        }

        $wallets = $this->walletRepository->getActiveWalletsByUser((string) $user->id);

        $createdAny = $this->ensureWalletsForActiveCurrencies($user, $wallets);
        if ($createdAny) {
            $wallets = $this->walletRepository->getActiveWalletsByUser((string) $user->id);
        }

        return $wallets
            ->load('currency')
            ->map(fn(Wallet $w) => [
                'id'             => (string) $w->id,
                'currency_id'    => (string) $w->currency_id,
                'currency_code'  => $w->currency->iso_code ?? '—',
                'currency_symbol'=> $w->currency->symbol   ?? '$',
                'balance'        => (float) $w->balance,
                'status'         => $w->status,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Movimientos paginados de la wallet de plataforma para una moneda dada.
     */
    public function getMovements(string $currencyId, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $wallet = $this->resolveWallet($currencyId);

        if (! $wallet) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return $this->movementRepository->getByWallet((string) $wallet->id, $perPage);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resuelve la wallet de plataforma para la moneda dada.
     * Caché guarda solo el wallet ID (string) para evitar serializar modelos Eloquent.
     */
    public function resolveWallet(string $currencyId): ?Wallet
    {
        $walletId = Cache::remember(
            $this->cacheKey($currencyId),
            self::CACHE_TTL,
            function () use ($currencyId): ?string {
                $user = $this->resolvePlatformUser();
                if (! $user) {
                    return null;
                }

                $wallet = $this->walletRepository->findWalletByUserAndCurrency(
                    (string) $user->id,
                    $currencyId
                );

                return $wallet ? (string) $wallet->id : null;
            }
        );

        return $walletId ? Wallet::find($walletId) : null;
    }

    /**
     * Resuelve el usuario sistema de la plataforma, creándolo si aún no existe.
     * Caché guarda solo el user ID (string) para evitar serializar modelos Eloquent.
     */
    private function resolvePlatformUser(): ?User
    {
        $userId = Cache::remember('platform_user_id', self::CACHE_TTL, function (): ?string {
            $user = User::firstOrCreate(
                ['email' => PlatformUserSeeder::PLATFORM_EMAIL],
                [
                    'username'          => PlatformUserSeeder::PLATFORM_USERNAME,
                    'first_name'        => 'Playrke',
                    'last_name'         => 'Platform',
                    'country_id'        => Country::where('iso_code_2', 'VE')->first()?->id,
                    'referral_code'     => app(UserRepositoryInterface::class)->generateUniqueReferralCode(),
                    'password'          => Hash::make(Str::random(64)),
                    'status'            => 'ACTIVE',
                    'email_verified_at' => now(),
                    'is_system_account' => true,
                ]
            );

            return $user ? (string) $user->id : null;
        });

        return $userId ? User::find($userId) : null;
    }

    private function cacheKey(string $currencyId): string
    {
        return "platform_wallet_{$currencyId}";
    }

    /**
     * Garantiza una wallet de plataforma por cada moneda activa (ej: monedas dadas
     * de alta después). El listado de monedas activas se cachea (cambia poco) y el
     * firstOrCreate solo se ejecuta para monedas sin wallet todavía, evitando un
     * INSERT...ON DUPLICATE innecesario en cada carga del dashboard.
     *
     * @param  \Illuminate\Support\Collection<int, Wallet> $existingWallets Wallets activas ya cargadas del usuario.
     * @return bool true si se creó al menos una wallet nueva (el caller debe refrescar su colección).
     */
    private function ensureWalletsForActiveCurrencies(User $user, \Illuminate\Support\Collection $existingWallets): bool
    {
        $activeCurrencyIds = Cache::remember(
            'currencies_active_ids',
            self::CACHE_TTL,
            fn () => Currency::where('status', true)->pluck('id')->map(fn ($id) => (string) $id)->all()
        );

        $existingCurrencyIds = $existingWallets
            ->pluck('currency_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $missingCurrencyIds = array_diff($activeCurrencyIds, $existingCurrencyIds);

        foreach ($missingCurrencyIds as $currencyId) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency_id' => $currencyId],
                ['balance' => 0.00, 'status' => 'ACTIVE']
            );
        }

        return count($missingCurrencyIds) > 0;
    }

    /**
     * Registra en auditoría un depósito o retiro manual de la wallet de plataforma,
     * dejando constancia de qué superadmin ejecutó la operación.
     */
    private function logCashAudit(
        string $action,
        Wallet $wallet,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $description
    ): void {
        Audit::create([
            'user_id'        => Auth::id(),
            'actor_type'     => Auth::check() ? 'user' : 'system',
            'auditable_type' => Wallet::class,
            'auditable_id'   => (string) $wallet->id,
            'event'          => "platform_wallet_{$action}",
            // Sin json_encode() manual: old_values/new_values ya tienen cast
            // 'array' en Audit, Eloquent se encarga de (de)serializar — igual
            // que Auditable::auditChanges() (ver ese trait para el porqué).
            'old_values'     => ['balance' => $balanceBefore],
            'new_values'     => [
                'balance'     => $balanceAfter,
                'amount'      => $amount,
                'description' => $description,
            ],
            'url'        => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);
    }
}
