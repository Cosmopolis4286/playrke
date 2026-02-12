<?php

namespace App\Services\Roles\Admin\Customer;

use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Admin\Customer\AdminCustomerDTO;
use App\Exceptions\Domain\Roles\Admin\Customer\AdminCustomerException;
use App\Http\Resources\Roles\Admin\Customer\AdminCustomerResource;
use App\Models\Pivots\Roles\Customer\CustomerCurrency;
use App\Models\Pivots\Roles\Customer\CustomerGame;
use App\Models\Roles\Customer;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio responsable de operaciones CRUD
 * sobre comercios en contexto Admin.
 *
 * Responsabilidades:
 * - Orquestar creación y mantenimiento del agregado Customer
 * - Coordinar persistencia de entidades relacionadas
 * - Aplicar reglas de dominio del comercio
 *
 * NO retorna booleanos.
 * NO maneja HTTP.
 * FALLA lanzando excepciones de dominio.
 */
final class AdminCustomerCrudService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    /**
     * Crear un comercio completo (User + Customer + Wallet).
     *
     * @param AdminCustomerDTO $dto
     * @return string Contraseña generada para el comercio
     *
     * @throws AdminCustomerException
     */
    public function create(AdminCustomerDTO $dto): string
    {
        return DB::transaction(function () use ($dto) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Usuario
            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('customer');

            // 2. Código único
            $uniqueCode = $this->userService->createUniqueCode($user->id);

            // 3. Comercio
            $customer = $this->customerRepository->create([
                'name'           => $dto->name,
                'phone'          => $dto->phone,
                'commission'     => $dto->commission,
                'country_id'     => $dto->country,
                'status'         => 'ACTIVE',
                'user_id'        => $user->id,
                'unique_code_id' => $uniqueCode->id,
            ]);

            if (! $customer) {
                throw AdminCustomerException::creationFailed();
            }

            // 4. Relaciones
            $this->syncCurrencies($customer, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($customer, $dto->allowedGames);

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
     * @param Customer $customer
     * @return AdminCustomerResource
     */
    public function show(Customer $customer): AdminCustomerResource
    {
        $customer->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currenciesActive:id,symbol,description',
            'gamesActive:id,name',
        ]);

        return new AdminCustomerResource($customer);
    }

    /**
     * Actualizar comercio y relaciones.
     *
     * @param Customer $customer
     * @param AdminCustomerDTO $dto
     *
     * @throws AdminCustomerException
     */
    public function update(Customer $customer, AdminCustomerDTO $dto): void
    {
        DB::transaction(function () use ($customer, $dto) {

            $this->customerRepository->update(
                $customer->id,
                array_filter([
                    'name'       => $dto->name,
                    'phone'      => $dto->phone,
                    'commission' => $dto->commission,
                    'country_id' => $dto->country,
                ])
            );

            $this->syncCurrencies($customer, $dto->currency, $dto->otherCurrencies);
            $this->syncGames($customer, $dto->allowedGames);

            if ($dto->email !== null || $dto->username !== null) {
                $customer->user()->update(array_filter([
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
        Customer $customer,
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
            CustomerCurrency::updateOrCreate(
                ['customer_id' => $customer->id, 'level' => 1],
                [
                    'currency_id' => $principalCurrencyId,
                    'status'      => 'ACTIVE',
                ]
            );
        }

        // Secundarias → inactivar las que sobran
        CustomerCurrency::where('customer_id', $customer->id)
            ->where('level', 2)
            ->whereNotIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'INACTIVE']);

        // Activar existentes
        CustomerCurrency::where('customer_id', $customer->id)
            ->where('level', 2)
            ->whereIn('currency_id', $secondaryCurrencyIds)
            ->update(['status' => 'ACTIVE']);

        // Insertar faltantes
        $existing = CustomerCurrency::where('customer_id', $customer->id)
            ->where('level', 2)
            ->pluck('currency_id')
            ->all();

        $missing = array_diff($secondaryCurrencyIds, $existing);

        if ($missing) {
            CustomerCurrency::insert(
                array_map(fn($id) => [
                    'customer_id' => $customer->id,
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
        Customer $customer,
        ?array $activeGameIds
    ): void {
        $activeGameIds = collect($activeGameIds ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        CustomerGame::where('customer_id', $customer->id)
            ->whereNotIn('game_id', $activeGameIds)
            ->update(['status' => 'INACTIVE']);

        CustomerGame::where('customer_id', $customer->id)
            ->whereIn('game_id', $activeGameIds)
            ->update(['status' => 'ACTIVE']);

        $existing = CustomerGame::where('customer_id', $customer->id)
            ->pluck('game_id')
            ->all();

        $missing = array_diff($activeGameIds, $existing);

        if ($missing) {
            CustomerGame::insert(
                array_map(fn($id) => [
                    'customer_id' => $customer->id,
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
     * @param Customer $customer
     * @return bool True si el cambio fue exitoso
     *
     * @throws RuntimeException Si el comercio está eliminado
     */
    public function toggleStatus(Customer $customer): bool
    {
        if ($customer->status === 'DELETED') {
            throw AdminCustomerException::invalidStatusChange();
        }

        // Usa changeStatus con userRelation para sincronizar Customer y User
        $newStatus = $customer->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->customerRepository->changeStatus($customer, $newStatus, 'user');
    }

    /**
     * Eliminación lógica (soft delete) del comercio.
     * También marca el usuario asociado como DELETED.
     *
     * @param Customer $customer
     * @return bool True si la eliminación fue exitosa
     *
     * @throws RuntimeException Si el comercio ya está eliminado
     */
    public function delete(Customer $customer): bool
    {
        if ($customer->status === 'DELETED') {
            throw AdminCustomerException::alreadyDeleted();
        }

        // Usa softDelete con userRelation para sincronizar Customer y User
        return $this->customerRepository->softDelete(
            model: $customer,
            status: 'DELETED',
            relationsToDetach: [],
            userRelation: 'user'
        );
    }

    /**
     * Eliminación definitiva del comercio y su usuario asociado.
     *
     * @param Customer $customer
     *
     * @throws AdminCustomerException
     */
    public function destroy(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {

            $customer->loadMissing(['currencies', 'games', 'user']);

            // Desvincula relaciones
            $customer->currencies()->detach();
            $customer->games()->detach();

            // Elimina wallets del usuario
            $this->walletRepository->deleteByUserId($customer->user_id);

            // Guarda referencia al usuario antes de eliminar el comercio
            $user = $customer->user;
            $userId = $customer->user_id;

            // Elimina definitivamente el comercio
            $customer->forceDelete();

            // Verifica si el usuario tiene otros comercios activos
            $hasOtherCustomers = Customer::where('user_id', $userId)
                ->where('id', '!=', $customer->id)
                ->exists();

            // Si no tiene otros comercios, elimina el usuario
            if (! $hasOtherCustomers && $user) {
                $user->forceDelete();
            }
        });
    }
}
