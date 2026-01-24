<?php

namespace App\Services\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Customer\CustomerExchangeRate;
use App\Services\BaseService;
use App\Services\Roles\RoleUserService;
use DomainException;
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
     * Crear/registrar una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto
     * @return bool
     *
     * @throws \DomainException Si el userId no es válido o no se puede resolver.
     */
    public function create(ExchangeRateDTO $dto): bool
    {
        $userId = $this->roleUserService->resolveUser($dto->tokenId)['id'];
        return $this->crud->create($dto, $userId);
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

    /**
     * Actualizar una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto
     * @return bool
     *
     * @throws \DomainException Si el userId no es válido o no se puede resolver.
     */
    public function update(ExchangeRateDTO $dto): bool
    {
        $userId = $this->roleUserService->resolveUser($dto->tokenId)['id'];
        $rolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);
        return $this->crud->update($dto, $rolePrefix);
    }

    /**
     * Obtiene la clase del modelo correspondiente según el ID del usuario.
     *
     * @param string|null $userId El ID del usuario. Si es nulo, se resuelve el usuario actual.
     * @return class-string|null La clase del modelo asociada al rol del usuario o null si no hay usuario.
     *
     * @throws \DomainException Cuando el rol es inválido.
     */
    private function getModelClassByUserId(?string $userId = null): ?string
    {
        if (!$userId) {
            $user = $this->roleUserService->resolveUser();
            if (empty($user['id'])) {
                return null;
            }
            $userId = $user['id'];
        }

        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        return match ($role) {
            'commerce' => CommerceExchangeRate::class,
            'customer' => CustomerExchangeRate::class,
            default => throw new DomainException("Rol inválido: {$role}"),
        };
    }

    /**
     * Cambia el estado (activo/inactivo) de una tasa de cambio según el rol del usuario.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     *
     * @throws \DomainException Si el rol es inválido
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si no se encuentra la tasa
     *
     * @return bool True si la transacción fue exitosa, false en caso de error
     */
    public function changeExchangeRateStatus(string $exchangeRateId, ?string $status = null): bool
    {
        $modelClass = $this->getModelClassByUserId();
        $model = $modelClass::findOrFail($exchangeRateId);

        return $this->changeStatus($model, $status);
    }

    /**
     * Eliminar comercio (soft delete)
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     *
     * @throws \DomainException Si el rol es inválido
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si no se encuentra la tasa
     *
     * @return bool True si la transacción fue exitosa, false en caso de error
     */
    public function softDeleteExchangeRate(string $exchangeRateId): bool
    {
        $modelClass = $this->getModelClassByUserId();
        $model = $modelClass::findOrFail($exchangeRateId);

        return $this->softDelete($model);
    }

    /**
     * Actualizar todas las tasas de cambio desde proveedor externo.
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
