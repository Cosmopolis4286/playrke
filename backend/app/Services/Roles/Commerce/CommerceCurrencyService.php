<?php

namespace App\Services\Roles\Commerce;

use App\Repositories\Roles\Commerce\CommerceCurrencyRepository;
use DomainException;

class CommerceCurrencyService
{
    public function __construct(
        protected CommerceCurrencyRepository $repository
    ) {}

    /**
     * Obtiene monedas disponibles para un comercio, opcionalmente excluyendo una moneda.
     *
     * @param mixed $commerceId ID del comercio.
     * @return array Lista de IDs de monedas disponibles.
     */
    public function getAvailableCurrencies($commerceId): array
    {
        if (!$commerceId) {
            throw new DomainException('El ID del comercio es requerido.');
        }

        return $this->repository->getAvailableCurrenciesIds($commerceId);
    }

    /**
     * Obtiene currency_id con su level mínimo para un comercio.
     * Retorna array indexado: currency_id => level.
     *
     * @param mixed $commerceId ID del comercio.
     * @return array<string, int>
     */
    public function getAvailableCurrenciesWithLevel($commerceId): array
    {
        if (!$commerceId) {
            throw new DomainException('El ID del comercio es requerido.');
        }

        return $this->repository->getAvailableCurrenciesWithLevel($commerceId);
    }

    /**
     * Actualiza el estado de un registro.
     *
     * @param mixed $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->repository->updateStatus($id, $status);
    }

    /**
     * Activa todos los registros asociados a un comercio.
     *
     * @param mixed $commerceId
     * @return int Cantidad de registros actualizados.
     */
    public function activateByCommerce($commerceId): int
    {
        return $this->repository->activateByCommerce($commerceId);
    }

    /**
     * Desactiva todos los registros asociados a un comercio.
     *
     * @param mixed $commerceId
     * @return int Cantidad de registros actualizados.
     */
    public function deactivateByCommerce($commerceId): int
    {
        return $this->repository->deactivateByCommerce($commerceId);
    }
}
