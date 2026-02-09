<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerContextDTO;
use App\DTOs\Roles\Seller\Manager\ManagerSellerDTO;
use App\Exceptions\Domain\Roles\Seller\Manager\ManagerSellerException;
use App\Http\Resources\Roles\Seller\Manager\ManagerSellerResource;
use App\Models\Pivots\Roles\Commerce\CommerceCurrency;
use App\Models\Pivots\Roles\Commerce\CommerceGame;
use App\Models\Roles\Commerce;
use App\Services\UserService;
use App\Traits\HashedTableDataTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
final class ManagerSellerCrudService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly UserService $userService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly SellerRepositoryInterface $sellerRepository,
        private readonly CountryRepositoryInterface $countries,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly GameRepositoryInterface $games,
        private readonly TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Obtener contexto de vendedores del usuario manager.
     *
     * @param int|null $userId ID del usuario manager
     * @return ManagerSellerContextDTO Contexto de vendedores para la vista Inertia
     */
    public function context(int $userId): ManagerSellerContextDTO
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->sellerRepository->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: ['manager_user_id' => $userId],
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
        );

        $collection = $source instanceof LengthAwarePaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $source instanceof LengthAwarePaginator
            ? [
                'total'        => $source->total(),
                'per_page'     => $source->perPage(),
                'current_page' => $source->currentPage(),
                'last_page'    => $source->lastPage(),
                'from'         => $source->firstItem(),
                'to'           => $source->lastItem(),
            ]
            : null;

        return new ManagerSellerContextDTO(
            sellers: $this->processCollectionTableData($collection, $labels, $rowKeys),
            pagination: $pagination,
            countries: $this->countries->getActive(['id', 'name']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
        );
    }

    /**
     * Obtener todos los comercios.
     *
     * @return Collection<int, Commerce>
     */
    public function getAll(): Collection
    {
        return $this->sellerRepository->all();
    }

    /**
     * Crear un comercio completo (User + Commerce + Wallet).
     *
     * @param ManagerSellerDTO $dto
     * @return string Contraseña generada para el comercio
     *
     * @throws ManagerSellerException
     */
    public function create(ManagerSellerDTO $dto): string
    {
        return DB::transaction(function () use ($dto) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Usuario
            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('commerce');

            // 2. Código único
            $uniqueCode = $this->userService->createUniqueCode($user->id);

            // 3. Comercio
            $commerce = $this->sellerRepository->create([
                'name'           => $dto->name,
                'phone'          => $dto->phone,
                'country_id'     => $dto->country,
                'status'         => 'ACTIVE',
                'user_id'        => $user->id,
                'unique_code_id' => $uniqueCode->id,
            ]);

            if (! $commerce) {
                throw ManagerSellerException::creationFailed();
            }

            // 4. Relaciones
            $this->syncCurrencies($commerce, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($commerce, $dto->allowedGames);

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
     * @param Commerce $commerce
     * @return ManagerSellerResource
     */
    public function show(Commerce $commerce): ManagerSellerResource
    {
        $commerce->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currenciesActive:id,symbol,description',
            'gamesActive:id,name',
        ]);

        return new ManagerSellerResource($commerce);
    }

    /**
     * Actualizar comercio y relaciones.
     *
     * @param Commerce $commerce
     * @param ManagerSellerDTO $dto
     *
     * @throws ManagerSellerException
     */
    public function update(Commerce $commerce, ManagerSellerDTO $dto): void
    {
        DB::transaction(function () use ($commerce, $dto) {

            $this->sellerRepository->update(
                $commerce->id,
                array_filter([
                    'name'       => $dto->name,
                    'phone'      => $dto->phone,
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
     * Sincroniza juegos permitidos.
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
            throw ManagerSellerException::invalidStatusChange();
        }

        // Usa changeStatus con userRelation para sincronizar Commerce y User
        $newStatus = $commerce->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->sellerRepository->changeStatus($commerce, $newStatus, 'user');
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
            throw ManagerSellerException::alreadyDeleted();
        }

        // Usa softDelete con userRelation para sincronizar Commerce y User
        return $this->sellerRepository->softDelete(
            model: $commerce,
            status: 'DELETED',
            relationsToDetach: [],
            userRelation: 'user'
        );
    }

    /**
     * Eliminación definitiva del comercio y su usuario asociado.
     *
     * @param Commerce $commerce
     *
     * @throws ManagerSellerException
     */
    public function destroy(Commerce $commerce): void
    {
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
