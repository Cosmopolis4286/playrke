<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExchangeRateCrud
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerce,
        protected CustomerExchangeRateRepositoryInterface $customer,
    ) {}

    /**
     * Crea un nuevo tipo de cambio para un rol específico.
     *
     * @param ExchangeRateDTO $dto
     * @param string $role Rol del propietario: 'commerce' | 'customer'
     * @return void
     *
     * @throws DomainException Si el rol no es válido
     */
    public function create(ExchangeRateDTO $dto, string $role): void
    {
        DB::transaction(fn() => $this->repo($role)->create($dto->toArray()));
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
     * Cambia el estado (activo/inactivo) del tipo de cambio.
     *
     * @param ExchangeRateDTO $dto Debe contener ID válido
     * @param string $role Rol del propietario: 'commerce' | 'customer'
     * @return void
     *
     * @throws DomainException Si el ID no está definido o rol inválido
     */
    public function toggleStatus(ExchangeRateDTO $dto, string $role): void
    {
        throw_if(!$dto->id, DomainException::class, 'ID requerido para cambiar estado.');

        $this->repo($role)->toggleStatus($dto->id);
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
