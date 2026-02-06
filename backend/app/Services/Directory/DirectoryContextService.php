<?php

namespace App\Services\Directory;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\Shared\DocumentTypeRepositoryInterface;
use App\DTOs\Directory\DirectoryContextDTO;
use App\Models\Banks\BankVenezuela;
use App\Repositories\Directories\Fiat\Venezuela\PagoMovilRepository;
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
 * - Construir data normalizada para tablas Inertia
 *
 * No contiene lógica de dominio.
 */
final class DirectoryContextService
{
    use HashedTableDataTrait;

    /**
     * Labels visibles de columnas para tabla.
     */
    private const DIRECTORY_COLUMNS = [
        'Titular',
        'Documento',
        'Teléfono',
        'Banco',
        'Status',
    ];

    /**
     * Keys fuente para mapear valores desde el modelo.
     */
    private const DIRECTORY_ROW_KEYS = [
        'holder_name',
        'document',
        'phone',
        'Bank.name',
        'status_badge',
    ];

    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly BankVenezuela $bankVenezuela,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly PagoMovilRepository $directoryRepository,
        private readonly CountryRepositoryInterface $countryRepository
    ) {}

    /**
     * Resuelve el contexto del directorio según país.
     *
     * @param int $userId ID del usuario
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     *
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
            directories: $this->getDirectoriesTableData($userId),
            documentTypes: $country
                ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
                : collect(),
            roleUser: $role,
        );
    }

    /**
     * Obtiene directorios del usuario listos para tabla Inertia.
     *
     * @param int $userId ID del usuario
     *
     * @return array
     */
    public function getAll(int $userId): array
    {
        return $this->getDirectoriesTableData($userId);
    }

    /**
     * Construye el contexto de tabla para directorios:
     * - IDs hasheados
     * - Keys normalizadas
     * - Columnas para frontend
     *
     * @param int $userId ID del usuario
     *
     * @return array
     */
    private function getDirectoriesTableData(int $userId): array
    {
        $directories = $this->directoryRepository->getByUser($userId);

        return $this->processCollectionTableData(
            $directories,
            self::DIRECTORY_COLUMNS,
            self::DIRECTORY_ROW_KEYS
        );
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
