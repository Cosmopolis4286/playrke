<?php

namespace App\Services\Directory;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Shared\DocumentTypeRepositoryInterface;
use App\DTOs\Directory\DirectoryContextDTO;
use App\Models\Banks\BankVenezuela;
use App\Repositories\Directory\DirectoryRepository;
use App\Services\Currency\CurrencyService;
use App\Services\Roles\RoleUserService;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de contexto para el directorio.
 *
 * Responsabilidades:
 * - Resolver contexto de usuario
 * - Cargar datos necesarios para la vista (bancos, tipos de documento)
 * - Preparar DTO para la vista
 */
class DirectoryContextService
{
    public function __construct(
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRate,
        private readonly CurrencyService $currency,
        private readonly RoleUserService $roleUserService,
        private readonly BankVenezuela $bankVenezuela,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly DirectoryRepository $directoryRepository,
        private readonly CountryRepositoryInterface $countryRepository
    ) {}

    /**
     * Resuelve el contexto del directorio de acuerdo al codigo ISO.
     *
     * @param string|null $countryCode Codigo ISO del país (opcional, por defecto VE).
     * @return DirectoryContextDTO Contexto listo para la vista.
     *
     * @throws DomainException Si no se puede resolver el rol activo.
     */
    public function resolve(?string $countryCode = 'VE'): DirectoryContextDTO
    {
        ['id' => $userId, 'authenticated' => $isLogged] = $this->roleUserService->resolveUser();

        $userRolePrefix = $this->roleUserService->getActiveUserRolePrefix($userId);

        // Obtener el país según el código ISO (por defecto VE)
        $country = $this->countryRepository->findByIsoCode2($countryCode, ['id', 'name', 'iso_code_2']);

        // Cargar bancos venezolanos ordenados alfabéticamente
        $banks = $this->bankVenezuela
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        // Cargar tipos de documento filtrados por país
        $documentTypes = $country
            ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
            : collect();

        // Cargar directorios del usuario autenticado
        $directories = $isLogged
            ? $this->directoryRepository->getAllByUser((string) $userId)
            : collect();

        return new DirectoryContextDTO(
            currencies: $isLogged ? $this->currency->getAvailableCurrencies($userId, true, true) : collect(),
            exchangeRates: $this->resolveExchangeRatesByRole($userRolePrefix, $userId),
            roleUser: $userRolePrefix,
            tokenId: $isLogged ? '' : $userId,
            banks: $banks,
            documentTypes: $documentTypes,
            directories: $directories
        );
    }

    /**
     * Resuelve las tasas de cambio según el prefijo de rol activo.
     *
     * @param string $role Prefijo de rol activo (ej. 'commerce', 'player').
     * @param int $userId ID del usuario o entidad relacionada.
     *
     * @return Collection Lista de tasas de cambio disponibles.
     */
    protected function resolveExchangeRatesByRole(string $role, int $userId): Collection
    {
        // Definir ID de la entidad para búsqueda basada en el rol
        $entityId = $this->roleUserService->getUserRoleEntityId($userId, $role);

        return match ($role) {
            'commerce' => $this->commerceExchangeRate->getAvailableExchangeRates($entityId),
            'customer', 'admin' => collect(),
            default => collect(),
        };
    }

    /**
     * Obtener todas las tasas de cambio filtradas según contexto y rol.
     *
     * @param string|null $publicToken Token público opcional
     * @return array Listado de tasas de cambio
     */
    public function getAll(?string $publicToken = null): array
    {
        ['id' => $userId, 'authenticated' => $isLogged] = $this->roleUserService->resolveUser($publicToken);
        $userRolePrefix = $isLogged ? $this->roleUserService->getActiveUserRolePrefix($userId) ?? '' : '';

        return $this->resolveExchangeRatesByRole($userRolePrefix, $userId)->toArray();
    }
}
