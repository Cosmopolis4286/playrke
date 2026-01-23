<?php

namespace App\Services\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Services\BaseService;
use App\Services\Roles\RoleUserService;
use Illuminate\Http\Request;

/**
 * Servicio de dominio para gestión de tasas de cambio.
 *
 * Responsabilidades:
 * - Exponer casos de uso del dominio
 * - Orquestar servicios especializados
 * - Mantener a los controllers desacoplados
 *
 * NO contiene lógica HTTP ni infraestructura.
 */
class ExchangeRateService extends BaseService
{
    /**
     * Contexto al que aplica la tasa (prefijo de rol o token público).
     *
     * @var string|null
     */
    protected ?string $role = null;

    /**
     * Constructor del servicio.
     *
     * @param RoleUserService $roleUserService
     * @param ExchangeRateContextService $context
     * @param ExchangeRateCrudService $crud
     * @param ExchangeRateUpdaterService $updater
     */
    public function __construct(
        protected RoleUserService $roleUserService,
        protected ExchangeRateContextService $context,
        protected ExchangeRateCrudService $crud,
        protected ExchangeRateUpdaterService $updater,
    ) {}

    /**
     * Obtener contexto para visualización de tasas.
     *
     * @param Request $request
     * @return array
     */
    public function context(Request $request): array
    {
        $token = $request->attributes->get('public_user');
        return $this->context->resolve($token)->toView();
    }

    /**
     * Crear una nueva tasa de cambio.
     *
     * @param ExchangeRateDTO $dto
     * @return void
     *
     * @throws \DomainException Si el userId no es válido o no se puede resolver.
     */
    public function create(ExchangeRateDTO $dto): void
    {
        $userId = $this->roleUserService->resolveUser($dto->tokenId)['id'];
        $this->crud->create($dto, $userId);
    }

    /**
     * Obtener tasa de cambio para visualización.
     *
     * @param string $exchangeRateId
     *
     * @return ExchangeRateResource
     * @throws \DomainException Si el userId no es válido o no se puede resolver.
     */
    public function getByExchangeRate(string $exchangeRateId): ExchangeRateResource
    {
        $userId = $this->roleUserService->resolveUser()['id'];

        return $this->crud->show($exchangeRateId, $userId);
    }


    public function changeExchangeRateStatus(string $id): void
    {
        return $this->changeStatus();
    }

    /**
     * Actualizar tasas desde proveedor externo.
     *
     * @param string $token ID del token de usuario por api publica (opcinal)
     * @return bool
     */
    public function updateRates(?string $token = null): bool
    {
        $userId = $this->roleUserService->resolveUser($token)['id'];
        return $this->updater->updateRates($userId);
    }

    /**
     * Obtener contexto para visualización del historico de tasas de cambio.
     *
     * @param Request $request
     * @return array
     */
    public function historicalContext() {}
}
