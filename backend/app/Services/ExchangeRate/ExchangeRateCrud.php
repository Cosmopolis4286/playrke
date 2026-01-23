<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExchangeRateCrud
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerce,
        protected CustomerExchangeRateRepositoryInterface $customer,
        protected RoleUserService $roleUserService
    ) {}

    /**
     * Crea un nuevo tipo de cambio para un rol específico.
     *
     * @param ExchangeRateDTO $dto
     * @param int $userId ID del usuario
     * @return void
     *
     * @throws \DomainException Si el rol no es válido o no identificado.
     */
    public function create(ExchangeRateDTO $dto, int $userId): void
    {
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!$rolePrefix) {
            throw new \DomainException('Rol inválido o no identificado.');
        }

        // Nombre de la columna foreign key asociada al rol
        $roleForeignKey = "{$rolePrefix}_id";

        $rolePrefixID = $this->roleUserService->getUserRoleEntityId($userId, $rolePrefix);

        $data = $dto->toArray() + [$roleForeignKey => $rolePrefixID];

        DB::transaction(fn() => $this->repo($rolePrefix)->create($data));
    }

    /**
     * Obtener tasa de cambio para visualización.
     * @param string $exchangeRateId
     * @param int $userId
     */
    public function show(string $exchangeRateId, int $userId): ?Model
    {
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!$rolePrefix) {
            throw new \DomainException('Rol inválido o no identificado.');
        }

        return $this->repo($rolePrefix)->find($exchangeRateId, ['id', 'amount', 'status']);
    }

    /**
     * Actualiza un tipo de cambio existente.
     *
     * @param ExchangeRateDTO $dto Debe contener ID válido
     * @param string $role Rol del propietario: 'commerce' | 'customer'
     * @return void
     *
     * @throws DomainException Si el ID no está definido o rol inválido
     */
    public function update(ExchangeRateDTO $dto, string $role): void
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido para actualizar.');

        DB::transaction(fn() => $this->repo($role)->update($dto->id, $dto->toArray()));
    }

    /**
     * Elimina un tipo de cambio por ID.
     *
     * @param ExchangeRateDTO $dto Debe contener ID válido
     * @param string $role Rol del propietario: 'commerce' | 'customer'
     * @return void
     *
     * @throws DomainException Si el ID no está definido o rol inválido
     */
    public function delete(ExchangeRateDTO $dto, string $role): void
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido para eliminar.');

        $this->repo($role)->delete($dto->id);
    }

    /**
     * Devuelve el repositorio correspondiente según el rol.
     *
     * @param string $role
     * @return CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
     *
     * @throws DomainException Si el rol no es válido
     */
    private function repo(string $role): CommerceExchangeRateRepositoryInterface|CustomerExchangeRateRepositoryInterface
    {
        return match ($role) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            default => throw new DomainException("Rol inválido: {$role}"),
        };
    }
}
