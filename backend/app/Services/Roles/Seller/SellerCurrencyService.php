<?php

namespace App\Services\Roles\Seller;

use App\Contracts\Roles\Seller\SellerCurrencyRepositoryInterface;
use InvalidArgumentException;

class SellerCurrencyService
{
    protected SellerCurrencyRepositoryInterface $repository;

    public function __construct(SellerCurrencyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Elimina una relación por su ID
     */
    public function deleteRelation(string $relationId) : bool
    {
        return $this->repository->deleteRelation($relationId);
    }

    /**
     * Obtiene monedas disponibles para un vendedor
     */
    public function getAvailableCurrencies(string $sellerId, bool $activeOnly = true) : array
    {
        return $this->repository->getAvailableCurrencies($sellerId, $activeOnly);
    }

    /**
     * Obtiene el nivel de operación para un vendedor en un país y moneda
     */
    public function getOperationLevel(string $sellerId, string $countryId, string $currencyId) : ?int
    {
        return $this->repository->getOperationLevel($sellerId, $countryId, $currencyId);
    }

    /**
     * Obtiene estadísticas generales de las relaciones
     */
    public function getStatistics() : array
    {
        return $this->repository->getRelationStatistics();
    }

    /**
     * Actualiza el nivel de operación para un vendedor en un país y moneda
     *
     * @throws InvalidArgumentException
     */
    public function updateOperationLevel(string $sellerId, string $countryId, string $currencyId, int $level) : bool
    {
        if ($level < 1 || $level > 10) {
            throw new InvalidArgumentException('El nivel debe estar entre 1 y 10');
        }

        return $this->repository->updateOperationLevel($sellerId, $countryId, $currencyId, $level);
    }

    /**
     * Cambia el estado activo/inactivo de una relación
     */
    public function updateStatus(string $relationId, bool $status) : bool
    {
        return $this->repository->updateStatus($relationId, $status);
    }
}
