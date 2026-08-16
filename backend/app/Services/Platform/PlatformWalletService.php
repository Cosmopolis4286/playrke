<?php

namespace App\Services\Platform;

use App\Contracts\UserRepositoryInterface;
use App\Contracts\WalletMovementRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Models\Audit;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMovement;
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
     * @param  string $currencyId  ID de la moneda de la venta
     * @param  float  $amount      Monto de la comisión en la moneda indicada por $currencyId
     * @param  string $description Referencia (ej: "RKE – Sala #abc123")
     */
    public function creditRke(string $currencyId, float $amount, string $description = 'Comisión RKE'): void
    {
        if ($amount <= 0) {
            return;
        }

        $wallet = $this->resolveWallet($currencyId);

        if (! $wallet) {
            // Si aún no existe la wallet de plataforma para esta moneda, no bloqueamos la venta
            return;
        }

        $balanceBefore = (float) $wallet->balance;

        $this->walletRepository->incrementBalance((string) $wallet->id, $amount);
        $wallet->refresh();

        $this->movementRepository->record([
            'wallet_id'      => (string) $wallet->id,
            'user_id'        => (string) $wallet->user_id,
            'type'           => WalletMovement::TYPE_RKE_COMMISSION,
            'status'         => WalletMovement::STATUS_COMPLETED,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => (float) $wallet->refresh()->balance,
            'description'    => $description,
        ]);

        // Invalidar caché de saldo
        Cache::forget($this->cacheKey($currencyId));
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

        $balanceBefore = (float) $wallet->balance;

        $this->walletRepository->incrementBalance((string) $wallet->id, $amount);
        $wallet->refresh();

        $this->movementRepository->record([
            'wallet_id'      => (string) $wallet->id,
            'user_id'        => (string) $wallet->user_id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_COMPLETED,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => (float) $wallet->balance,
            'description'    => $description,
        ]);

        Cache::forget($this->cacheKey($currencyId));

        $this->logCashAudit('deposit', $wallet, $amount, $balanceBefore, (float) $wallet->balance, $description);
    }

    /**
     * Registra un retiro manual de la wallet de plataforma (ej. extracción de fondos).
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

        if ((float) $wallet->balance < $amount) {
            throw new \RuntimeException('Fondos insuficientes en la wallet de plataforma.');
        }

        $balanceBefore = (float) $wallet->balance;

        $this->walletRepository->decrementBalance((string) $wallet->id, $amount);
        $wallet->refresh();

        $this->movementRepository->record([
            'wallet_id'      => (string) $wallet->id,
            'user_id'        => (string) $wallet->user_id,
            'type'           => WalletMovement::TYPE_WITHDRAWAL,
            'status'         => WalletMovement::STATUS_COMPLETED,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => (float) $wallet->balance,
            'description'    => $description,
        ]);

        Cache::forget($this->cacheKey($currencyId));

        $this->logCashAudit('withdraw', $wallet, $amount, $balanceBefore, (float) $wallet->balance, $description);
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

        // Garantiza una wallet por cada moneda activa (ej: monedas dadas de alta después).
        foreach (Currency::where('status', true)->get() as $currency) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency_id' => $currency->id],
                ['balance' => 0.00, 'status' => 'ACTIVE']
            );
        }

        return $this->walletRepository
            ->getActiveWalletsByUser((string) $user->id)
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
