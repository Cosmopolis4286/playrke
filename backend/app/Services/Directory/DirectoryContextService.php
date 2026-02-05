<?php

namespace App\Services\Directory;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\Shared\DocumentTypeRepositoryInterface;
use App\DTOs\Directory\DirectoryContextDTO;
use App\Models\Banks\BankVenezuela;
use App\Repositories\Directory\DirectoryRepository;
use App\Services\Roles\RoleUserService;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de aplicación para construir el contexto del Directorio.
 *
 * Responsabilidades:
 * - Resolver usuario y rol activo
 * - Cargar datos necesarios para la vista
 * - Delegar resolución de entidades según rol
 *
 * No contiene lógica de dominio.
 */
final class DirectoryContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly BankVenezuela $bankVenezuela,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly DirectoryRepository $directoryRepository,
        private readonly CountryRepositoryInterface $countryRepository
    ) {}

    /**
     * Resuelve el contexto del directorio según país.
     *
     * @param int $userId ID del usuario
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     * @return DirectoryContextDTO Contexto listo para la vista
     *
     * @throws DomainException
     */
    public function resolve(int $userId, string $countryCode = 'VE'): DirectoryContextDTO
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        $country = $this->countryRepository
            ->findByIsoCode2($countryCode, ['id', 'name', 'iso_code_2']);

        return new DirectoryContextDTO(
            banks: $this->getBanks(),

            directories: $this->processDirectories($userId),

            documentTypes: $country
                ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
                : collect(),

            roleUser: $role,
        );
    }

    /**
     * Obtiene todas las tasas de cambio según contexto del usuario.
     *
     * @param string $userId ID del usuario
     * @return array
     */
    public function getAll(string $userId): array
    {
        return $this->processDirectories($userId);
    }

    /**
     * Aplica transformación de tablas con
     * IDs hasheados, columnas y keys normalizadas para frontend.
     *
     * @param string $userId ID del usuario
     * @return array
     */
    private function processDirectories(string $userId): array
    {
        $directories =  $this->directoryRepository->getAllByUser($userId);

        // Define columnas visibles y keys fuente para la tabla
        $columns = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_label'];

        // Aplica transformación de contexto para tablas Inertia
        $context = $this->processContextTableData($context, $rolePrefix, $columns, $rowKeys);

        return $context;
    }

    /**
     * Obtiene bancos venezolanos ordenados.
     *
     * @return Collection
     */
    private function getBanks(): Collection
    {
        return $this->bankVenezuela
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
    }
}
