<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateContextDTO;
use App\Models\UserPreference;
use App\Services\Currency\CurrencyService;
use App\Services\Roles\RoleEntityResolver;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio responsable de construir el contexto
 * necesario para la visualización de tasas de cambio.
 *
 * Diferencia entre usuarios autenticados y públicos,
 * resolviendo monedas, tasas y rol activo.
 *
 * NO contiene lógica de autenticación ni acceso HTTP.
 */
final class ExchangeRateContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        private readonly CustomerExchangeRateRepositoryInterface $customerExchangeRate,
        private readonly AdminExchangeRateRepositoryInterface $adminExchangeRate,
        private readonly CurrencyService $currency,
        private readonly UserContextService $userContext,
        private readonly RoleUserService $roleUserService,
        private readonly RoleEntityResolver $roleEntityResolver,
    ) {}

    /**
     * Resuelve el contexto para la visualización de tasas de cambio.
     *
     * - Usuarios autenticados reciben monedas y tasas según su rol.
     * - Usuarios públicos obtienen acceso limitado mediante token.
     *
     * @param string|null $publicToken Token público opcional.
     * @return ExchangeRateContextDTO Contexto listo para la vista.
     *
     * @throws DomainException Si no se puede resolver el usuario o rol activo.
     */
    public function resolve(?string $publicToken = null): ExchangeRateContextDTO
    {
        $userId = $this->userContext->resolveUserId($publicToken);
        $isLogged = $this->userContext->isAuthenticated();

        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        $autoUpdateEnabled = (bool) UserPreference::getPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'auto_update_enabled',
            false
        );

        $bcvAutoUpdateEnabled = (bool) UserPreference::getPreference(
            $userId,
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'bcv_auto_update_enabled',
            false
        );

        return new ExchangeRateContextDTO(
            currencies: $isLogged
                ? $this->currency->getAvailableCurrencies($userId, true, true)
                : collect(),

            exchangeRates: $this->resolveExchangeRatesByRole(
                $userRolePrefix,
                $userId
            ),

            roleUser: $userRolePrefix,

            // En acceso público exponemos el userId resuelto como tokenId
            tokenId: $isLogged ? '' : (string) $userId,

            autoUpdateEnabled: $autoUpdateEnabled,

            bcvAutoUpdateEnabled: $bcvAutoUpdateEnabled,
        );
    }

    /**
     * Resuelve las tasas de cambio según el prefijo de rol activo.
     *
     * Antes 'customer' caía al default (Collection vacía) pese a que
     * ExchangeRateCrudService/ExchangeRateUpdaterService sí soportan ese rol
     * de punta a punta — un customer podía crear/editar su propia tasa pero
     * nunca la veía listada aquí. 'treasurer' gestiona la tasa OFICIAL igual
     * que admin/superadmin (no tiene entidad propia, ver RoleEntityResolver).
     *
     * @param string $role Prefijo de rol activo (ej. 'commerce', 'customer').
     * @param int $userId ID del usuario autenticado o resuelto por token.
     *
     * @return Collection Lista de tasas de cambio disponibles.
     */
    protected function resolveExchangeRatesByRole(string $role, int $userId): Collection
    {
        return match ($role) {
            'commerce' => $this->commerceExchangeRate->getAvailableExchangeRates(
                $this->roleEntityResolver->resolveEntityId($userId, $role)
            ),
            'customer' => $this->customerExchangeRate->getAvailableExchangeRates(
                $this->roleEntityResolver->resolveEntityId($userId, $role)
            ),
            'admin', 'superadmin', 'treasurer' => $this->adminExchangeRate->getAvailableExchangeRates(),
            default                            => new Collection(),
        };
    }


    /**
     * Obtener todas las tasas de cambio filtradas según contexto y rol.
     *
     * @param string|null $publicToken Token público opcional.
     * @return array Listado de tasas de cambio.
     *
     * @throws DomainException Si no se puede resolver el usuario.
     */
    public function getAll(?string $publicToken = null): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        return $this->resolveExchangeRatesByRole($role, $userId)->toArray();
    }

    /**
     * Resuelve el contexto histórico de tasas de cambio para una moneda.
     *
     * @param string $currencyId ID de la moneda a consultar.
     * @param string|null $publicToken Token público opcional.
     * @return array Datos para la vista histórica.
     *
     * @throws DomainException Si no se puede resolver el usuario o rol activo.
     */
    public function resolveHistorical(string $currencyId, ?string $publicToken = null): array
    {
        $userId = $this->userContext->resolveUserId($publicToken);
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        $history = match ($role) {
            'commerce' => $this->commerceExchangeRate->getHistoryByCurrency(
                $this->roleEntityResolver->resolveEntityId($userId, $role),
                $currencyId
            ),
            // CustomerExchangeRateRepositoryInterface no tiene getHistoryByCurrency
            // (nombre distinto: getRateHistory) — mismo dato, firma propia.
            'customer' => $this->customerExchangeRate->getRateHistory(
                $this->roleEntityResolver->resolveEntityId($userId, $role),
                $currencyId
            ),
            'admin', 'superadmin', 'treasurer' => $this->adminExchangeRate->getHistoryByCurrency($currencyId),
            default                            => new Collection(),
        };

        return [
            'exchangeRates' => $history,
            'currencyId'    => $currencyId,
            'roleUser'      => $role,
        ];
    }
}
