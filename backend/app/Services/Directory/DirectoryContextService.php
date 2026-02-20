<?php

namespace App\Services\Directory;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\Shared\DocumentTypeRepositoryInterface;
use App\DTOs\Directory\DirectoryContextDTO;
use App\Models\Banks\BankBrazil;
use App\Models\Banks\BankVenezuela;
use App\Repositories\Directories\Fiat\Brazil\PixKeyRepository;
use App\Repositories\Directories\Fiat\Venezuela\PagoMovilRepository;
use App\Services\Currency\CurrencyService;
use App\Services\Roles\RoleUserService;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de aplicación para construir el contexto del Directorio.
 *
 * Responsabilidades:
 * - Resolver usuario y rol activo
 * - Cargar datos necesarios para la vista según el país seleccionado
 * - Construir data normalizada para tablas Inertia
 *
 * No contiene lógica de dominio.
 */
final class DirectoryContextService
{
    use HashedTableDataTrait;

    /**
     * Columnas y keys para Venezuela (Pago Móvil).
     */
    private const VE_COLUMNS = [
        'Titular',
        'Documento',
        'Teléfono',
        'Banco',
        'Status',
    ];

    private const VE_ROW_KEYS = [
        'holder_name',
        'document',
        'phone',
        'Bank.name',
        'status_badge',
    ];

    /**
     * Columnas y keys para Brasil (PIX).
     */
    private const BR_COLUMNS = [
        'Nombre',
        'Tipo PIX',
        'Clave PIX',
        'Banco',
    ];

    private const BR_ROW_KEYS = [
        'name',
        'pix_key_type',
        'pix_key_value',
        'Bank.name',
    ];

    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly BankVenezuela $bankVenezuela,
        private readonly BankBrazil $bankBrazil,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly PagoMovilRepository $directoryRepository,
        private readonly PixKeyRepository $pixKeyRepository,
        private readonly CountryRepositoryInterface $countryRepository,
        private readonly CurrencyService $currencyService,
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

        $currencies = $this->currencyService->getAvailableCurrenciesWithLevel($userId);

        return new DirectoryContextDTO(
            banks: $this->getBanksByCountry($countryCode),
            directories: $this->getDirectoriesTableData($userId, $countryCode),
            documentTypes: ($country && $countryCode === 'VE')
                ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
                : collect(),
            roleUser: $role,
            currencies: $currencies,
        );
    }

    /**
     * Obtiene directorios del usuario listos para tabla Inertia según el país.
     *
     * @param int $userId ID del usuario
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     *
     * @return array
     */
    public function getAll(int $userId, string $countryCode = 'VE'): array
    {
        return $this->getDirectoriesTableData($userId, $countryCode);
    }

    /**
     * Retorna el contexto completo (bancos + documentTypes + directorios) para un país.
     * Usado por el endpoint AJAX al cambiar de tab.
     *
     * @param int $userId
     * @param string $countryCode
     * @return array
     */
    public function getContextByCountry(int $userId, string $countryCode): array
    {
        $country = $this->countryRepository
            ->findByIsoCode2($countryCode, ['id', 'name', 'iso_code_2']);

        return [
            'banks'         => $this->getBanksByCountry($countryCode),
            'documentTypes' => ($country && $countryCode === 'VE')
                ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
                : collect(),
            'directories'   => $this->getDirectoriesTableData($userId, $countryCode),
        ];
    }

    /**
     * Construye el contexto de tabla para directorios según el país:
     * - IDs hasheados
     * - Keys normalizadas
     * - Columnas para frontend
     *
     * @param int $userId ID del usuario
     * @param string $countryCode Código ISO2 del país
     *
     * @return array
     */
    private function getDirectoriesTableData(int $userId, string $countryCode = 'VE'): array
    {
        if ($countryCode === 'BR') {
            return $this->getPixKeysTableData($userId);
        }

        return $this->getPagoMovilTableData($userId);
    }

    /**
     * Obtiene datos de tabla para Pago Móvil (Venezuela).
     *
     * @param int $userId
     * @return array
     */
    private function getPagoMovilTableData(int $userId): array
    {
        $directories = $this->directoryRepository->getByUser($userId);

        return $this->processCollectionTableData(
            $directories,
            self::VE_COLUMNS,
            self::VE_ROW_KEYS
        );
    }

    /**
     * Obtiene datos de tabla para PIX (Brasil).
     *
     * @param int $userId
     * @return array
     */
    private function getPixKeysTableData(int $userId): array
    {
        $pixKeys = $this->pixKeyRepository->getByUser((string) $userId);

        return $this->processCollectionTableData(
            $pixKeys,
            self::BR_COLUMNS,
            self::BR_ROW_KEYS
        );
    }

    /**
     * Obtiene bancos según el país.
     *
     * @param string $countryCode
     * @return Collection
     */
    private function getBanksByCountry(string $countryCode): Collection
    {
        if ($countryCode === 'BR') {
            return $this->bankBrazil
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return $this->bankVenezuela
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
    }
}
