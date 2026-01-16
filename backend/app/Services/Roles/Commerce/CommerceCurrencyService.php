<?php

namespace App\Services\Roles\Commerce;

use App\Repositories\Roles\Commerce\CommerceCurrencyRepository;
use Illuminate\Database\Eloquent\Collection;
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
     * @param mixed|null $exceptCurrencyId ID de moneda a excluir (opcional).
     * @return array Lista de IDs de monedas disponibles.
     */
    public function getAvailableCurrencies($commerceId, $exceptCurrencyId = null): array
    {
        if (!$commerceId) {
            throw new DomainException('El ID del comercio es requerido.');
        }

        return $this->repository->getAvailableCurrencies($commerceId, $exceptCurrencyId);
    }

    /**
     * Obtiene todos los registros activos de países y monedas para un comercio.
     *
     * @param mixed $commerceId ID del comercio.
     * @param array $fields Campos a seleccionar.
     * @return Collection
     */
    public function getAllAvailableCountriesCurrencies($commerceId, array $fields = ['*']): Collection
    {
        if (!$commerceId) {
            throw new DomainException('El ID del comercio es requerido.');
        }

        return $this->repository->getAllAvailableCountriesCurrencies($commerceId, $fields);
    }

    /**
     * Verifica si existe la combinación comercio-país-moneda.
     *
     * @param mixed $commerceId
     * @param mixed $countryId
     * @param mixed $currencyId
     * @return bool
     */
    public function existsCombination($commerceId, $countryId, $currencyId): bool
    {
        if (!$commerceId || !$countryId || !$currencyId) {
            return false;
        }

        return $this->repository->existsCombination($commerceId, $countryId, $currencyId);
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
