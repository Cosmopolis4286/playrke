<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceCurrencyRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Http\Resources\ExchangeRate\ExchangeRateResource;
use App\Http\Resources\ExchangeRate\AdminExchangeRateResource;
use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\Models\UserPreference;
use App\Services\Roles\RoleEntityResolver;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
use DomainException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio ExchangeRate.
 *
 * Responsabilidades:
 * - Resolver contexto de usuario (auth / token)
 * - Resolver rol y entidad asociada
 * - Delegar operaciones a servicios especializados
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura HTTP.
 */
final class ExchangeRateService
{
    public function __construct(
        private readonly UserContextService $userContext,
        private readonly RoleUserService $roleUserService,
        private readonly RoleEntityResolver $roleEntityResolver,
        private readonly ExchangeRateContextService $context,
        private readonly ExchangeRateCrudService $crud,
        private readonly ExchangeRateUpdaterService $updater,
        private readonly ExchangeRateApiService $api,
        private readonly BcvExchangeRateService $bcvApi,
        private readonly AdminExchangeRateRepositoryInterface $adminExchangeRate,
        private readonly CommerceCurrencyRepositoryInterface $commerceCurrencyRepository,
        private readonly CustomerExchangeRateRepositoryInterface $customerExchangeRate,
    ) {}

    /**
     * Obtener contexto para visualización de tasas.
     *
     * @param string|null $publicToken Token público de API
     * @return array Contexto listo para la vista
     */
    public function context(?string $publicToken = null): array
    {
        return $this->context
            ->resolve($publicToken)
            ->toView();
    }

    /**
     * Obtener todas las tasas de cambio filtradas según contexto y rol.
     *
     * @param string|null $publicToken Token público opcional
     * @return array Listado de tasas de cambio
     */
    public function getAll(?string $publicToken = null): array
    {
        return $this->context->getAll($publicToken);
    }

    /**
     * Crear una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto DTO con los datos de la tasa de cambio
     * @return bool True si la creación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la creación
     */
    public function create(ExchangeRateDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->crud->create($dto, $userId);
    }

    /**
     * Obtener una tasa de cambio para visualización.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return ExchangeRateResource Recurso para visualización
     *
     * @throws DomainException Si no se puede resolver el usuario o no existe la tasa
     */
    public function show(string $exchangeRateId): ExchangeRateResource|AdminExchangeRateResource
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->show($exchangeRateId, $userId);
    }

    /**
     * Actualizar una tasa de cambio.
     *
     * @param ExchangeRateDTO $dto DTO con datos actualizados
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function update(ExchangeRateDTO $dto): bool
    {
        $userId = $this->userContext->resolveUserId($dto->tokenId);

        return $this->crud->update($dto, $userId);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de una tasa de cambio.
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si el cambio fue exitoso
     *
     * @throws DomainException Si no se puede resolver el usuario o falla el cambio
     */
    public function toggleStatus(string $exchangeRateId): bool
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->changeStatusByRole($exchangeRateId, $userId);
    }

    /**
     * Eliminar una tasa de cambio (soft delete).
     *
     * @param string $exchangeRateId ID de la tasa de cambio
     * @return bool True si la eliminación fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la eliminación
     */
    public function delete(string $exchangeRateId): bool
    {
        $userId = $this->userContext->resolveUserId();

        return $this->crud->deleteByRole($exchangeRateId, $userId);
    }

    /**
     * Retorna las tasas crudas obtenidas de la API externa para mostrar en confirmación.
     *
     * Solo retorna tasas de monedas que el comercio tiene activas en commercecurrencies.
     * Excluye el campo "Promedio" de la respuesta.
     *
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return array|null Retorna un arreglo con las tasas si la llamada fue exitosa,
     *                    o null si ocurrió algún error.
     */
    public function getRawRates(?string $publicToken = null): ?array
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (!in_array($role, ['admin', 'superadmin', 'treasurer', 'commerce', 'customer'])) {
            return null;
        }

        $rates = $this->api->fetch();

        if (empty($rates)) {
            return null;
        }

        if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            // For admin users, get all available currency ISO codes from database
            $availableIsoCodes = $this->adminExchangeRate->getAvailableCurrenciesIsoCodes();

            // Filter external rates to only include available currencies
            $filteredRates = collect($rates)
                ->filter(
                    fn($value, $key) =>
                    $key !== 'Promedio'
                        && $value != 0
                        && in_array($key, $availableIsoCodes, true)
                )
                ->toArray();

            return $this->overrideVesWithBcvIfEnabled($filteredRates, $userId);
        } elseif ($role === 'commerce') {

            $commerceId = $this->roleEntityResolver->resolveEntityId($userId, $role);

            $availableIsoCodes = $this->commerceCurrencyRepository
                ->getAvailableCurrenciesIsoCodes($commerceId);

            $filteredRates = collect($rates)
                ->filter(
                    fn($value, $key) =>
                    $key !== 'Promedio'
                        && $value != 0
                        && in_array($key, $availableIsoCodes, true)
                )
                ->toArray();

            return $this->overrideVesWithBcvIfEnabled($filteredRates, $userId);
        } elseif ($role === 'customer') {

            $customerId = $this->roleEntityResolver->resolveEntityId($userId, $role);

            $availableIsoCodes = $this->customerExchangeRate
                ->getAvailableCurrenciesIsoCodes($customerId);

            $filteredRates = collect($rates)
                ->filter(
                    fn($value, $key) =>
                    $key !== 'Promedio'
                        && $value != 0
                        && in_array($key, $availableIsoCodes, true)
                )
                ->toArray();

            return $this->overrideVesWithBcvIfEnabled($filteredRates, $userId);
        }

        return null;
    }

    /**
     * Si el usuario tiene activada la actualización automática vía BCV
     * y la tasa contiene la moneda VES, sustituye su valor por la tasa
     * oficial publicada por el BCV.
     *
     * @param array $rates Tasas a ajustar (iso_code => value)
     * @param int $userId ID del usuario resuelto
     * @return array Tasas ajustadas
     */
    private function overrideVesWithBcvIfEnabled(array $rates, int $userId): array
    {
        if (!array_key_exists('VES', $rates)) {
            return $rates;
        }

        $bcvEnabled = (bool) UserPreference::getPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'bcv_auto_update_enabled',
            false
        );

        if (!$bcvEnabled) {
            return $rates;
        }

        $bcvRate = $this->bcvApi->fetch();

        if ($bcvRate !== null) {
            $rates['VES'] = $bcvRate;
        }

        return $rates;
    }

    /**
     * Actualizar tasas con los valores confirmados por el usuario.
     *
     * @param array $rates Tasas confirmadas por el usuario (iso_code => value)
     * @param string|null $publicToken Token público opcional para resolver usuario
     * @return bool True si la actualización fue exitosa
     *
     * @throws DomainException Si no se puede resolver el usuario o falla la actualización
     */
    public function updateRates(array $rates, ?string $publicToken = null): bool
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        if (in_array($role, ['admin', 'superadmin', 'treasurer'])) {
            // Para admin/superadmin/treasurer, actualizar todas las tasas (tasa oficial) sin filtrar por comercio
            $filteredRates = collect($rates)
                ->filter(fn($value, $key) => $key !== 'Promedio' && $value != 0)
                ->toArray();
        } elseif ($role === 'commerce') {
            $commerceId = $this->roleEntityResolver
                ->resolveEntityId($userId, $role);

            $availableIsoCodes = $this->commerceCurrencyRepository
                ->getAvailableCurrenciesIsoCodes($commerceId);

            $filteredRates = collect($rates)
                ->filter(
                    fn($value, $key) =>
                    $key !== 'Promedio'
                        && $value != 0
                        && in_array($key, $availableIsoCodes, true)
                )
                ->toArray();
        } elseif ($role === 'customer') {
            // Antes cualquier customer caía al 'else => return false': podía
            // crear/editar su propia tasa (ExchangeRateCrudService sí lo
            // soporta) pero jamás sincronizarla en lote con "Actualizar".
            $customerId = $this->roleEntityResolver
                ->resolveEntityId($userId, $role);

            $availableIsoCodes = $this->customerExchangeRate
                ->getAvailableCurrenciesIsoCodes($customerId);

            $filteredRates = collect($rates)
                ->filter(
                    fn($value, $key) =>
                    $key !== 'Promedio'
                        && $value != 0
                        && in_array($key, $availableIsoCodes, true)
                )
                ->toArray();
        } else {
            return false;
        }

        return $this->updater->updateRates($userId, $filteredRates);
    }

    /**
     * Obtener contexto histórico de tasas de cambio por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @param string|null $publicToken Token público opcional
     * @return array Contexto histórico para la vista
     */
    public function historicalContext(string $currencyId, ?string $publicToken = null): array
    {
        return $this->context->resolveHistorical($currencyId, $publicToken);
    }

    /**
     * Obtener la tasa oficial USD/VES publicada por el BCV.
     *
     * @return float|null Tasa USD/VES o null si no se pudo obtener.
     */
    public function getBcvRate(): ?float
    {
        return $this->bcvApi->fetch();
    }

    /**
     * Activar la actualización automática de la tasa VES usando el BCV.
     *
     * @param string|null $publicToken Token público opcional
     * @return void
     */
    public function enableBcvAutoUpdate(?string $publicToken = null): void
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        UserPreference::setPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'bcv_auto_update_enabled',
            'true'
        );
    }

    /**
     * Desactivar la actualización automática de la tasa VES usando el BCV.
     *
     * @param string|null $publicToken Token público opcional
     * @return void
     */
    public function disableBcvAutoUpdate(?string $publicToken = null): void
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        UserPreference::setPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'bcv_auto_update_enabled',
            'false'
        );
    }

    /**
     * Obtener el estado actual de la actualización automática vía BCV.
     *
     * @param string|null $publicToken Token público opcional
     * @return bool
     */
    public function getBcvAutoUpdateStatus(?string $publicToken = null): bool
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        return (bool) UserPreference::getPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'bcv_auto_update_enabled',
            false
        );
    }

    /**
     * Clave de preferencia para el auto-update por moneda individual (todas
     * excepto VES, que usa 'bcv_auto_update_enabled' y la fuente oficial del
     * BCV en vez de open.er-api.com — ver overrideVesWithBcvIfEnabled()).
     */
    private function currencyAutoUpdateKey(string $isoCode): string
    {
        return 'auto_update_enabled:' . strtoupper($isoCode);
    }

    /**
     * Activar la actualización automática de una moneda individual (control
     * independiente del switch global "¿Actualización automática?" — un
     * usuario puede tener COP en automático y BRL en manual). Ver
     * HandleExchangeRatesCommand, que lee estas preferencias además de la
     * global.
     *
     * @param string $isoCode Código ISO de la moneda (ej. 'COP', 'BRL')
     * @param string|null $publicToken Token público opcional
     */
    public function enableCurrencyAutoUpdate(string $isoCode, ?string $publicToken = null): void
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        UserPreference::setPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            $this->currencyAutoUpdateKey($isoCode),
            'true'
        );
    }

    /**
     * Desactivar la actualización automática de una moneda individual.
     *
     * @param string $isoCode Código ISO de la moneda (ej. 'COP', 'BRL')
     * @param string|null $publicToken Token público opcional
     */
    public function disableCurrencyAutoUpdate(string $isoCode, ?string $publicToken = null): void
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        UserPreference::setPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            $this->currencyAutoUpdateKey($isoCode),
            'false'
        );
    }

    /**
     * Obtener el estado actual de la actualización automática de una moneda
     * individual.
     *
     * @param string $isoCode Código ISO de la moneda (ej. 'COP', 'BRL')
     * @param string|null $publicToken Token público opcional
     */
    public function getCurrencyAutoUpdateStatus(string $isoCode, ?string $publicToken = null): bool
    {
        $userId = $this->userContext->resolveUserId($publicToken);

        return (bool) UserPreference::getPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            $this->currencyAutoUpdateKey($isoCode),
            false
        );
    }
}
