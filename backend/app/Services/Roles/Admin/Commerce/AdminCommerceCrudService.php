<?php

namespace App\Services\Roles\Admin\Commerce;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Events\Admin\UserRegistered;
use App\Exceptions\Domain\Roles\Admin\Commerce\AdminCommerceException;
use App\Http\Resources\Roles\Admin\Commerce\AdminCommerceResource;
use App\Models\Directories\Fiat\Brazil\PixKey;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Models\Game;
use App\Models\Pivots\Roles\Commerce\CommerceCurrency;
use App\Models\Pivots\Roles\Commerce\CommerceGame;
use App\Models\User;
use App\Models\Roles\Commerce;
use App\Services\Platform\PlatformConfigService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio responsable de operaciones CRUD
 * sobre comercios en contexto Admin.
 *
 * Responsabilidades:
 * - Orquestar creación y mantenimiento del agregado Commerce
 * - Coordinar persistencia de entidades relacionadas
 * - Aplicar reglas de dominio del comercio
 *
 * NO retorna booleanos.
 * NO maneja HTTP.
 * FALLA lanzando excepciones de dominio.
 */
final class AdminCommerceCrudService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly PlatformConfigService $platformConfig,
    ) {}

    /**
     * Crear un comercio completo (User + Commerce + Wallet).
     *
     * @param AdminCommerceDTO $dto
     * @return string Contraseña generada para el comercio
     *
     * @throws AdminCommerceException
     */
    public function create(AdminCommerceDTO $dto): string
    {
        return DB::transaction(function () use ($dto) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Usuario
            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('commerce');

            // 2. Código único
            $uniqueCode = $this->userService->createUniqueCode($user->id);

            // 3. Comercio
            $commerce = $this->commerceRepository->create([
                'name'           => $dto->name,
                'phone'          => $dto->phone,
                'commission'     => $dto->commission,
                'country_id'     => $dto->country,
                'status'         => 'ACTIVE',
                'user_id'        => $user->id,
                'unique_code_id' => $uniqueCode->id,
            ]);

            if (! $commerce) {
                throw AdminCommerceException::creationFailed();
            }

            // 4. Relaciones
            $this->syncCurrencies($commerce, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($commerce, $dto->allowedGames);

            // 5. Wallet principal
            $this->walletRepository->create([
                'user_id'     => $user->id,
                'currency_id' => $dto->currency,
            ]);

            broadcast(new UserRegistered(
                userId: (string) $user->id,
                username: $user->username,
                email: $user->email,
                roleName: 'commerce',
                createdAt: $user->created_at->toISOString(),
            ));

            return $plainPassword;
        });
    }

    /**
     * Obtener comercio para visualización.
     *
     * @param Commerce $commerce
     * @return AdminCommerceResource
     */
    public function show(Commerce $commerce): AdminCommerceResource
    {
        $commerce->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currenciesActive:id,symbol,description',
            'gamesActive:id,name',
            'uniqueCode:id,rke_code',
            // Jerarquía real Comercializadora → Agencia → Vendedor (mismo
            // patrón que CommerceHierarchyService, aplicado a un único
            // comercio en vez del top-20 agregado del dashboard SuperAdmin).
            'sellers:id,name,phone,status,manager_user_id,subsidiary_id',
            'subsidiaries:id,name,status,commerce_id',
            'subsidiaries.sellers:id,name,phone,status,subsidiary_id',
        ])->loadCount('sellers');

        // Atributo dinámico (no persistido) para que AdminCommerceResource lo
        // exponga como campo normal — additional() no llega al JSON final
        // porque este resource se anida manualmente dentro de
        // response()->json() en el controller, en vez de devolverse como
        // respuesta HTTP directa (único caso en que Laravel aplica additional()).
        $commerce->is_default_commerce = $this->platformConfig->isDefaultCommerce((string) $commerce->id);

        // Cuentas de cobro (Directorio) del usuario dueño del comercio — no
        // de sus vendedores/agencias, que son usuarios distintos. Ambos
        // modelos (VE/BR) conviven porque commerces.country no restringe qué
        // país de directorio pudo haber registrado el usuario en el pasado;
        // se listan las que existan, sin asumir un único país.
        $commerce->directory_accounts = [
            'pago_movil' => PagoMovil::byUser((string) $commerce->user_id)
                ->with('bank:id,name')
                ->get(['id', 'holder_name', 'phone', 'bank_id', 'status'])
                ->map(fn (PagoMovil $p) => [
                    'id' => (string) $p->id,
                    'holder_name' => $p->holder_name,
                    'phone' => $p->phone,
                    'bank_name' => $p->bank?->name,
                    'status' => $p->status,
                ])->values(),
            'pix_keys' => PixKey::byUser((string) $commerce->user_id)
                ->with('bank:id,name')
                ->get(['id', 'holder_name', 'pix_key_type', 'pix_key_value', 'bank_id', 'status'])
                ->map(fn (PixKey $p) => [
                    'id' => (string) $p->id,
                    'holder_name' => $p->holder_name,
                    'pix_key_type' => $p->pix_key_type,
                    'pix_key_value' => $p->pix_key_value,
                    'bank_name' => $p->bank?->name,
                    'status' => $p->status,
                ])->values(),
        ];

        return new AdminCommerceResource($commerce);
    }

    /**
     * Actualizar comercio y relaciones.
     *
     * @param Commerce $commerce
     * @param AdminCommerceDTO $dto
     *
     * @throws AdminCommerceException
     */
    public function update(Commerce $commerce, AdminCommerceDTO $dto): void
    {
        DB::transaction(function () use ($commerce, $dto) {

            $this->commerceRepository->update(
                $commerce->id,
                array_filter([
                    'name'       => $dto->name,
                    'phone'      => $dto->phone,
                    'commission' => $dto->commission,
                    'country_id' => $dto->country,
                ])
            );

            $this->syncCurrencies($commerce, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($commerce, $dto->allowedGames);

            if ($dto->email !== null || $dto->username !== null) {
                $commerce->user()->update(array_filter([
                    'email'    => $dto->email,
                    'username' => $dto->username,
                ]));
            }
        });
    }

    /**
     * Sincroniza monedas del comercio.
     */
    protected function syncCurrencies(
        Commerce $commerce,
        ?int $principalCurrencyId,
        ?array $secondaryCurrencyIds
    ): void {
        $secondaryCurrencyIds = collect($secondaryCurrencyIds ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Principal
        if ($principalCurrencyId) {
            CommerceCurrency::updateOrCreate(
                ['commerce_id' => $commerce->id, 'level' => 1],
                [
                    'currency_id' => $principalCurrencyId,
                    'status'      => 'ACTIVE',
                ]
            );
        }

        // Secundarias → inactivar las que sobran
        CommerceCurrency::where('commerce_id', $commerce->id)
            ->where('level', 2)
            ->whereNotIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'INACTIVE']);

        // Activar existentes
        CommerceCurrency::where('commerce_id', $commerce->id)
            ->where('level', 2)
            ->whereIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'ACTIVE']);

        // Insertar faltantes
        $existing = CommerceCurrency::where('commerce_id', $commerce->id)
            ->where('level', 2)
            ->pluck('currency_id')
            ->all();

        $missing = array_diff($secondaryCurrencyIds, $existing);

        if ($missing) {
            CommerceCurrency::insert(
                array_map(fn($id) => [
                    'commerce_id' => $commerce->id,
                    'currency_id' => $id,
                    'level'       => 2,
                    'status'      => 'ACTIVE',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ], $missing)
            );
        }
    }

    /**
     * Sincroniza juegos permitidos y actualiza permisos específicos del usuario.
     */
    protected function syncGames(
        Commerce $commerce,
        ?array $activeGameIds
    ): void {
        $activeGameIds = collect($activeGameIds ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Obtener nombres de juegos para crear permisos
        $gamePermissions = [];
        if (!empty($activeGameIds)) {
            $games = Game::whereIn('id', $activeGameIds)->get(['id', 'name']);
            $gamePermissions = $games->map(fn($game) => 'games.' . strtolower($game->name))
                ->values()
                ->all();
        }

        // Actualizar permisos específicos de juego del usuario del comercio
        // (permisos no-juego quedan intactos: solo se tocan los 'games.*').
        $user = User::find($commerce->user_id);
        if ($user) {
            $currentGamePermissions = $user->specificPermissionGrants()
                ->active()
                ->where('permission_name', 'like', 'games.%')
                ->pluck('permission_name')
                ->all();

            $toRevoke = array_diff($currentGamePermissions, $gamePermissions);
            $toGrant  = array_diff($gamePermissions, $currentGamePermissions);

            if ($toRevoke !== []) {
                $user->revokeSpecificPermission($toRevoke);
            }
            if ($toGrant !== []) {
                $user->giveSpecificPermission($toGrant);
            }
        }

        CommerceGame::where('commerce_id', $commerce->id)
            ->whereNotIn('game_id', $activeGameIds)
            ->update(['status' => 'INACTIVE']);

        CommerceGame::where('commerce_id', $commerce->id)
            ->whereIn('game_id', $activeGameIds)
            ->update(['status' => 'ACTIVE']);

        $existing = CommerceGame::where('commerce_id', $commerce->id)
            ->pluck('game_id')
            ->all();

        $missing = array_diff($activeGameIds, $existing);

        if ($missing) {
            CommerceGame::insert(
                array_map(fn($id) => [
                    'commerce_id' => $commerce->id,
                    'game_id'     => $id,
                    'status'      => 'ACTIVE',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ], $missing)
            );
        }
    }

    /**
     * Alternar estado del comercio (ACTIVE / INACTIVE).
     * También sincroniza el estado del usuario asociado.
     *
     * @param Commerce $commerce
     * @return bool True si el cambio fue exitoso
     *
     * @throws RuntimeException Si el comercio está eliminado
     */
    public function toggleStatus(Commerce $commerce): bool
    {
        if ($commerce->status === 'DELETED') {
            throw AdminCommerceException::invalidStatusChange();
        }

        if ($commerce->status === 'ACTIVE' && $this->platformConfig->isDefaultCommerce((string) $commerce->id)) {
            throw AdminCommerceException::isDefaultCommerce();
        }

        // Usa changeStatus con userRelation para sincronizar Commerce y User
        $newStatus = $commerce->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->commerceRepository->changeStatus($commerce, $newStatus, 'user');
    }

    /**
     * Eliminación lógica (soft delete) del comercio.
     * También marca el usuario asociado como DELETED.
     *
     * @param Commerce $commerce
     * @return bool True si la eliminación fue exitosa
     *
     * @throws RuntimeException Si el comercio ya está eliminado
     */
    public function delete(Commerce $commerce): bool
    {
        if ($commerce->status === 'DELETED') {
            throw AdminCommerceException::alreadyDeleted();
        }

        if ($this->platformConfig->isDefaultCommerce((string) $commerce->id)) {
            throw AdminCommerceException::isDefaultCommerce();
        }

        // Usa softDelete con userRelation para sincronizar Commerce y User
        return $this->commerceRepository->softDelete(
            model: $commerce,
            status: 'DELETED',
            relationsToDetach: [],
            userRelation: 'user'
        );
    }

    /**
     * Alternar estado ACTIVE/INACTIVE de varios comercios en una sola
     * transacción — mismo patrón que AdminPlayerCrudService::toggleStatusBulk():
     * un comercio inválido (ej. ya eliminado) no aborta el lote completo, se
     * omite y se reporta en 'skipped'.
     *
     * @param array<int, string> $commerceIds
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    public function toggleStatusBulk(array $commerceIds): array
    {
        return DB::transaction(function () use ($commerceIds) {
            $updated = 0;
            $skipped = [];

            foreach (Commerce::whereIn('id', $commerceIds)->get() as $commerce) {
                try {
                    $this->toggleStatus($commerce);
                    $updated++;
                } catch (AdminCommerceException $e) {
                    $skipped[] = [
                        'id'     => (string) $commerce->id,
                        'name'   => $commerce->name,
                        'reason' => trans("errors.{$e->getMessage()}") ?: $e->getMessage(),
                    ];
                }
            }

            return ['updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * Eliminación lógica (soft delete) de varios comercios en una sola
     * transacción. Mismo criterio de omisión que toggleStatusBulk().
     *
     * @param array<int, string> $commerceIds
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    public function deleteBulk(array $commerceIds): array
    {
        return DB::transaction(function () use ($commerceIds) {
            $updated = 0;
            $skipped = [];

            foreach (Commerce::whereIn('id', $commerceIds)->get() as $commerce) {
                try {
                    $this->delete($commerce);
                    $updated++;
                } catch (AdminCommerceException $e) {
                    $skipped[] = [
                        'id'     => (string) $commerce->id,
                        'name'   => $commerce->name,
                        'reason' => trans("errors.{$e->getMessage()}") ?: $e->getMessage(),
                    ];
                }
            }

            return ['updated' => $updated, 'skipped' => $skipped];
        });
    }

    /**
     * Eliminación definitiva del comercio y su usuario asociado.
     *
     * @param Commerce $commerce
     *
     * @throws AdminCommerceException
     */
    public function destroy(Commerce $commerce): void
    {
        if ($this->platformConfig->isDefaultCommerce((string) $commerce->id)) {
            throw AdminCommerceException::isDefaultCommerce();
        }

        DB::transaction(function () use ($commerce) {

            $commerce->loadMissing(['currencies', 'games', 'user']);

            // Desvincula relaciones
            $commerce->currencies()->detach();
            $commerce->games()->detach();

            // Elimina wallets del usuario
            $this->walletRepository->deleteByUserId($commerce->user_id);

            // Guarda referencia al usuario antes de eliminar el comercio
            $user = $commerce->user;
            $userId = $commerce->user_id;

            // Elimina definitivamente el comercio
            $commerce->forceDelete();

            // Verifica si el usuario tiene otros comercios activos
            $hasOtherCommerces = Commerce::where('user_id', $userId)
                ->where('id', '!=', $commerce->id)
                ->exists();

            // Si no tiene otros comercios, elimina el usuario
            if (! $hasOtherCommerces && $user) {
                $user->forceDelete();
            }
        });
    }
}
