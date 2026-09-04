<?php

namespace App\Services\Roles\Admin\Treasurer;

use App\Contracts\Roles\Treasurer\TreasurerRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Admin\Treasurer\AdminTreasurerDTO;
use App\Events\Admin\UserRegistered;
use App\Exceptions\Domain\Roles\Admin\Treasurer\AdminTreasurerException;
use App\Http\Resources\Roles\Admin\Treasurer\AdminTreasurerResource;
use App\Models\Pivots\Roles\Treasurer\TreasurerCurrency;
use App\Models\Pivots\Roles\Treasurer\TreasurerGame;
use App\Models\Roles\Treasurer;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio responsable de operaciones CRUD
 * sobre tesoreros en contexto AdminTreasurer.
 *
 * Responsabilidades:
 * - Orquestar creación y mantenimiento del agregado Treasurer
 * - Coordinar persistencia de entidades relacionadas
 * - Aplicar reglas de dominio del tesorero
 *
 * NO retorna booleanos.
 * NO maneja HTTP.
 * FALLA lanzando excepciones de dominio.
 */
final class AdminTreasurerCrudService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TreasurerRepositoryInterface $treasurerRepository,
    ) {}

    /**
     * Crear un tesorero completo (User + Treasurer + Wallet).
     *
     * @param AdminTreasurerDTO $dto
     * @return string Contraseña generada para el tesorero
     *
     * @throws AdminTreasurerException
     */
    public function create(AdminTreasurerDTO $dto): string
    {
        return DB::transaction(function () use ($dto) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Usuario
            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('treasurer');

            // 2. Código único
            $uniqueCode = $this->userService->createUniqueCode($user->id);

            // 3. Comercio
            $treasurer = $this->treasurerRepository->create([
                'name'           => $dto->name,
                'phone'          => $dto->phone,
                'commission'     => $dto->commission,
                'country_id'     => $dto->country,
                'status'         => 'ACTIVE',
                'user_id'        => $user->id,
                'unique_code_id' => $uniqueCode->id,
            ]);

            if (! $treasurer) {
                throw AdminTreasurerException::creationFailed();
            }

            // 4. Relaciones
            $this->syncCurrencies($treasurer, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($treasurer, $dto->allowedGames);

            // 5. Wallet principal
            $this->walletRepository->create([
                'user_id'     => $user->id,
                'currency_id' => $dto->currency,
            ]);

            broadcast(new UserRegistered(
                userId: (string) $user->id,
                username: $user->username,
                email: $user->email,
                roleName: 'treasurer',
                createdAt: $user->created_at->toISOString(),
            ));

            return $plainPassword;
        });
    }

    /**
     * Obtener tesorero para visualización.
     *
     * @param Treasurer $treasurer
     * @return AdminTreasurerResource
     */
    public function show(Treasurer $treasurer): AdminTreasurerResource
    {
        $treasurer->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currenciesActive:id,symbol,description',
            'gamesActive:id,name',
        ]);

        return new AdminTreasurerResource($treasurer);
    }

    /**
     * Actualizar tesorero y relaciones.
     *
     * @param Treasurer $treasurer
     * @param AdminTreasurerDTO $dto
     *
     * @throws AdminTreasurerException
     */
    public function update(Treasurer $treasurer, AdminTreasurerDTO $dto): void
    {
        DB::transaction(function () use ($treasurer, $dto) {

            $this->treasurerRepository->update(
                $treasurer->id,
                array_filter([
                    'name'       => $dto->name,
                    'phone'      => $dto->phone,
                    'commission' => $dto->commission,
                    'country_id' => $dto->country,
                ])
            );

            $this->syncCurrencies($treasurer, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($treasurer, $dto->allowedGames);

            if ($dto->email !== null || $dto->username !== null) {
                $treasurer->user()->update(array_filter([
                    'email'    => $dto->email,
                    'username' => $dto->username,
                ]));
            }
        });
    }

    /**
     * Sincroniza monedas del tesorero.
     */
    protected function syncCurrencies(
        Treasurer $treasurer,
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
            TreasurerCurrency::updateOrCreate(
                ['treasurer_id' => $treasurer->id, 'level' => 1],
                [
                    'currency_id' => $principalCurrencyId,
                    'status'      => 'ACTIVE',
                ]
            );
        }

        // Secundarias → inactivar las que sobran
        TreasurerCurrency::where('treasurer_id', $treasurer->id)
            ->where('level', 2)
            ->whereNotIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'INACTIVE']);

        // Activar existentes
        TreasurerCurrency::where('treasurer_id', $treasurer->id)
            ->where('level', 2)
            ->whereIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'ACTIVE']);

        // Insertar faltantes
        $existing = TreasurerCurrency::where('treasurer_id', $treasurer->id)
            ->where('level', 2)
            ->pluck('currency_id')
            ->all();

        $missing = array_diff($secondaryCurrencyIds, $existing);

        if ($missing) {
            TreasurerCurrency::insert(
                array_map(fn($id) => [
                    'treasurer_id' => $treasurer->id,
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
     * Sincroniza juegos permitidos.
     */
    protected function syncGames(
        Treasurer $treasurer,
        ?array $activeGameIds
    ): void {
        $activeGameIds = collect($activeGameIds ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        TreasurerGame::where('treasurer_id', $treasurer->id)
            ->whereNotIn('game_id', $activeGameIds)
            ->update(['status' => 'INACTIVE']);

        TreasurerGame::where('treasurer_id', $treasurer->id)
            ->whereIn('game_id', $activeGameIds)
            ->update(['status' => 'ACTIVE']);

        $existing = TreasurerGame::where('treasurer_id', $treasurer->id)
            ->pluck('game_id')
            ->all();

        $missing = array_diff($activeGameIds, $existing);

        if ($missing) {
            TreasurerGame::insert(
                array_map(fn($id) => [
                    'treasurer_id' => $treasurer->id,
                    'game_id'     => $id,
                    'status'      => 'ACTIVE',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ], $missing)
            );
        }
    }

    /**
     * Alternar estado del tesorero (ACTIVE / INACTIVE).
     * También sincroniza el estado del usuario asociado.
     *
     * @param Treasurer $treasurer
     * @return bool True si el cambio fue exitoso
     *
     * @throws RuntimeException Si el tesorero está eliminado
     */
    public function toggleStatus(Treasurer $treasurer): bool
    {
        if ($treasurer->status === 'DELETED') {
            throw AdminTreasurerException::invalidStatusChange();
        }

        // Usa changeStatus con userRelation para sincronizar Treasurer y User
        $newStatus = $treasurer->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->treasurerRepository->changeStatus($treasurer, $newStatus, 'user');
    }

    /**
     * Eliminación lógica (soft delete) del tesorero.
     * También marca el usuario asociado como DELETED.
     *
     * @param Treasurer $treasurer
     * @return bool True si la eliminación fue exitosa
     *
     * @throws RuntimeException Si el tesorero ya está eliminado
     */
    public function delete(Treasurer $treasurer): bool
    {
        if ($treasurer->status === 'DELETED') {
            throw AdminTreasurerException::alreadyDeleted();
        }

        // Usa softDelete con userRelation para sincronizar Treasurer y User
        return $this->treasurerRepository->softDelete(
            model: $treasurer,
            status: 'DELETED',
            relationsToDetach: [],
            userRelation: 'user'
        );
    }

    /**
     * Eliminación definitiva del tesorero y su usuario asociado.
     *
     * @param Treasurer $treasurer
     *
     * @throws AdminTreasurerException
     */
    public function destroy(Treasurer $treasurer): void
    {
        DB::transaction(function () use ($treasurer) {

            $treasurer->loadMissing(['currencies', 'games', 'user']);

            // Desvincula relaciones
            $treasurer->currencies()->detach();
            $treasurer->games()->detach();

            // Elimina wallets del usuario
            $this->walletRepository->deleteByUserId($treasurer->user_id);

            // Guarda referencia al usuario antes de eliminar el tesorero
            $user = $treasurer->user;
            $userId = $treasurer->user_id;

            // Elimina definitivamente el tesorero
            $treasurer->forceDelete();

            // Verifica si el usuario tiene otros tesoreros activos
            $hasOtherTreasurers = Treasurer::where('user_id', $userId)
                ->where('id', '!=', $treasurer->id)
                ->exists();

            // Si no tiene otros tesoreros, elimina el usuario
            if (! $hasOtherTreasurers && $user) {
                $user->forceDelete();
            }
        });
    }
}
