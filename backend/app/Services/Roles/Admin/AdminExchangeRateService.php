<?php

namespace App\Services\Roles\Admin;

use App\Contracts\Roles\Admin\AdminRepositoryInterface;
use App\Exceptions\Domain\Roles\Admin\Administrator\AdministratorException;
use App\Services\ExchangeRate\ExchangeRateService;
use App\Services\Security\UserContextService;

/**
 * Servicio de aplicación para administradores gestionar tasas de cambio
 */
final class AdminExchangeRateService
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
        private readonly ExchangeRateService $exchangeRateService,
        private readonly UserContextService $userContext,
    ) {}

    /**
     * Obtener contexto para visualización de tasas de cambio para administradores
     *
     * @return array Contexto listo para la vista
     */
    public function getContext(): array
    {
        $userId = $this->userContext->resolveUserId();
        
        $admin = $this->adminRepository->getByUserId($userId);
        
        if (!$admin) {
            throw AdministratorException::notFound();
        }

        return $this->exchangeRateService->context();
    }

    /**
     * Obtener todas las tasas de cambio para administradores
     *
     * @return array Listado de tasas de cambio
     */
    public function getAllRates(): array
    {
        $userId = $this->userContext->resolveUserId();
        
        $admin = $this->adminRepository->getByUserId($userId);
        
        if (!$admin) {
            throw AdministratorException::notFound();
        }

        return $this->exchangeRateService->getAll();
    }

    /**
     * Obtener tasas crudas de la API externa para administradores
     *
     * @return array|null Tasas crutas o null si hay error
     */
    public function getRawRates(): ?array
    {
        $userId = $this->userContext->resolveUserId();
        
        $admin = $this->adminRepository->getByUserId($userId);
        
        if (!$admin) {
            throw AdministratorException::notFound();
        }

        return $this->exchangeRateService->getRawRates();
    }

    /**
     * Actualizar tasas de cambio con valores confirmados por el administrador
     *
     * @param array $rates Tasas confirmadas (iso_code => value)
     * @return bool True si la actualización fue exitosa
     */
    public function updateRates(array $rates): bool
    {
        $userId = $this->userContext->resolveUserId();
        
        $admin = $this->adminRepository->getByUserId($userId);
        
        if (!$admin) {
            throw AdministratorException::notFound();
        }

        return $this->exchangeRateService->updateRates($rates);
    }

    /**
     * Obtener contexto histórico de tasas por moneda
     *
     * @param string $currencyId ID de la moneda
     * @return array Contexto histórico
     */
    public function getHistoricalContext(string $currencyId): array
    {
        $userId = $this->userContext->resolveUserId();
        
        $admin = $this->adminRepository->getByUserId($userId);
        
        if (!$admin) {
            throw AdministratorException::notFound();
        }

        return $this->exchangeRateService->historicalContext($currencyId);
    }

    /**
     * Verificar si el administrador tiene permisos para gestionar tasas
     *
     * @return bool True si tiene permisos
     */
    public function canManageExchangeRates(): bool
    {
        $userId = $this->userContext->resolveUserId();
        
        $admin = $this->adminRepository->getByUserId($userId);
        
        if (!$admin) {
            return false;
        }

        return $admin->hasPermission('manage_exchange_rates');
    }
}
