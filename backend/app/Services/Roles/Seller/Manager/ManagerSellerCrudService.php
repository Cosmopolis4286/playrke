<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Exceptions\Domain\Roles\Seller\Manager\ManagerSellerException;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Game;
use App\Models\User;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use App\Models\Pivots\Roles\Seller\SellerGame;
use App\Models\Roles\Seller;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio responsable de operaciones CRUD
 * sobre comercios en contexto Admin.
 *
 * Responsabilidades:
 * - Orquestar creación y mantenimiento del agregado Seller
 * - Coordinar persistencia de entidades relacionadas
 * - Aplicar reglas de dominio del comercio
 *
 * NO retorna booleanos.
 * NO maneja HTTP.
 * FALLA lanzando excepciones de dominio.
 */
final class ManagerSellerCrudService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly SellerRepositoryInterface $sellerRepository,
    ) {}

    /**
     * Crear un vendedor completo (User + Seller + Wallet).
     *
     * @param ManagerSellerDTO $dto
     * @param int $managerUserId ID del usuario manager al que representa el vendedor
     *   — siempre el user_id de un Commerce (ver ManagerSellerService::create()),
     *   nunca el de una Subsidiary directamente.
     * @param string|null $subsidiaryId Agencia a la que pertenece el vendedor,
     *   si fue creado por un usuario 'subsidiary'. Null si reporta directo al
     *   Commerce sin agencia intermedia.
     * @return string Contraseña generada para el vendedor
     *
     * @throws ManagerSellerException
     */
    public function create(ManagerSellerDTO $dto, int $managerUserId, ?string $subsidiaryId = null): string
    {
        return DB::transaction(function () use ($dto, $managerUserId, $subsidiaryId) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Crear usuario
            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('seller');

            // 2. Registar vendedor
            $seller = $this->sellerRepository->create([
                'name' => $dto->name,
                'phone' => $dto->phone,
                'commission' => $dto->commission,
                'user_id' => $user->id,
                'country_id' => $dto->country,
                'manager_user_id' => $managerUserId,
                'subsidiary_id' => $subsidiaryId,
                'status' => 'ACTIVE',
            ]);

            if (!$seller) {
                throw ManagerSellerException::creationFailed();
            }

            // 4. Relaciones
            $this->syncCurrencies($seller, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($seller, $dto->allowedGames);

            // 5. Wallet principal
            $this->walletRepository->create([
                'user_id'     => $user->id,
                'currency_id' => $dto->currency,
            ]);

            return $plainPassword;
        });
    }

    /**
     * Obtener comercio para visualización.
     *
     * @param Seller $seller
     * @return ManagerSellerResource
     */
    public function show(Seller $seller): ManagerSellerResource
    {
        $seller->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currenciesActive:id,symbol,description',
            'gamesActive:id,name',
            'subsidiary:id,user_id',
        ]);

        return new ManagerSellerResource($seller);
    }

    /**
     * Actualizar comercio y relaciones.
     *
     * @param Seller $seller
     * @param ManagerSellerDTO $dto
     * @param int|null $managerUserId Nuevo Commerce padre (ya resuelto por
     *   ManagerSellerService::update()) — null si no se reasigna.
     * @param string|null $subsidiaryId Nueva Subsidiary padre — solo
     *   relevante cuando $managerUserId también se pasa; puede ser null
     *   explícito para "mover a reporte directo del Commerce, sin agencia".
     *
     * @throws ManagerSellerException
     */
    public function update(Seller $seller, ManagerSellerDTO $dto, ?int $managerUserId = null, ?string $subsidiaryId = null): void
    {
        DB::transaction(function () use ($seller, $dto, $managerUserId, $subsidiaryId) {

            $attributes = array_filter([
                'name'       => $dto->name,
                'phone'      => $dto->phone,
                'commission' => $dto->commission,
                'country_id' => $dto->country,
            ]);

            if ($managerUserId !== null) {
                $attributes['manager_user_id'] = $managerUserId;
                // subsidiary_id se asigna directo (sin array_filter, que
                // descartaría un null explícito) — reasignar a un Commerce
                // sin agencia intermedia es un valor válido, no "sin cambios".
                $attributes['subsidiary_id'] = $subsidiaryId;
            }

            $this->sellerRepository->update($seller->id, $attributes);

            $this->syncCurrencies($seller, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($seller, $dto->allowedGames);

            if ($dto->email !== null || $dto->username !== null) {
                $seller->user()->update(array_filter([
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
        Seller $seller,
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
            SellerCurrency::updateOrCreate(
                ['seller_id' => $seller->id, 'level' => 1],
                [
                    'currency_id' => $principalCurrencyId,
                    'status'      => 'ACTIVE',
                ]
            );
        }

        // Secundarias → inactivar las que sobran
        SellerCurrency::where('seller_id', $seller->id)
            ->where('level', 2)
            ->whereNotIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'INACTIVE']);

        // Activar existentes
        SellerCurrency::where('seller_id', $seller->id)
            ->where('level', 2)
            ->whereIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'ACTIVE']);

        // Insertar faltantes
        $existing = SellerCurrency::where('seller_id', $seller->id)
            ->where('level', 2)
            ->pluck('currency_id')
            ->all();

        $missing = array_diff($secondaryCurrencyIds, $existing);

        if ($missing) {
            SellerCurrency::insert(
                array_map(fn($id) => [
                    'seller_id' => $seller->id,
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
        Seller $seller,
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

        // Actualizar permisos específicos de juego del usuario del seller
        // (permisos no-juego quedan intactos: solo se tocan los 'games.*').
        $user = User::find($seller->user_id);
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

        SellerGame::where('seller_id', $seller->id)
            ->whereNotIn('game_id', $activeGameIds)
            ->update(['status' => 'INACTIVE']);

        SellerGame::where('seller_id', $seller->id)
            ->whereIn('game_id', $activeGameIds)
            ->update(['status' => 'ACTIVE']);

        $existing = SellerGame::where('seller_id', $seller->id)
            ->pluck('game_id')
            ->all();

        $missing = array_diff($activeGameIds, $existing);

        if ($missing) {
            SellerGame::insert(
                array_map(fn($id) => [
                    'seller_id' => $seller->id,
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
     * @param Seller $seller
     * @return bool True si el cambio fue exitoso
     *
     * @throws RuntimeException Si el comercio está eliminado
     */
    public function toggleStatus(Seller $seller): bool
    {
        if ($seller->status === 'DELETED') {
            throw ManagerSellerException::invalidStatusChange();
        }

        // Usa changeStatus con userRelation para sincronizar Seller y User
        $newStatus = $seller->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->sellerRepository->changeStatus($seller, $newStatus, 'user');
    }

    /**
     * Eliminación lógica (soft delete) del comercio.
     * También marca el usuario asociado como DELETED.
     *
     * @param Seller $seller
     * @return bool True si la eliminación fue exitosa
     *
     * @throws RuntimeException Si el comercio ya está eliminado
     */
    public function delete(Seller $seller): bool
    {
        if ($seller->status === 'DELETED') {
            throw ManagerSellerException::alreadyDeleted();
        }

        // Usa softDelete con userRelation para sincronizar Seller y User
        return $this->sellerRepository->softDelete(
            model: $seller,
            status: 'DELETED',
            relationsToDetach: [],
            userRelation: 'user'
        );
    }

    /**
     * Eliminación definitiva del comercio y su usuario asociado.
     *
     * @param Seller $seller
     *
     * @throws ManagerSellerException
     */
    public function destroy(Seller $seller): void
    {
        DB::transaction(function () use ($seller) {

            $seller->loadMissing(['currencies', 'games', 'user']);

            // Desvincula relaciones
            $seller->currencies()->detach();
            $seller->games()->detach();

            // Elimina wallets del usuario
            $this->walletRepository->deleteByUserId($seller->user_id);

            // Guarda referencia al usuario antes de eliminar el comercio
            $user = $seller->user;
            $userId = $seller->user_id;

            // Elimina definitivamente el comercio
            $seller->forceDelete();

            // Verifica si el usuario tiene otros comercios activos
            $hasOtherSellers = Seller::where('user_id', $userId)
                ->where('id', '!=', $seller->id)
                ->exists();

            // Si no tiene otros comercios, elimina el usuario
            if (! $hasOtherSellers && $user) {
                $user->forceDelete();
            }
        });
    }
}
