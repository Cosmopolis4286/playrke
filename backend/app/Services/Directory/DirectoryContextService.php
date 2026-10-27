<?php

namespace App\Services\Directory;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\Shared\DocumentTypeRepositoryInterface;
use App\DTOs\Directory\DirectoryContextDTO;
use App\Models\Banks\BankVenezuela;
use App\Repositories\Directories\Fiat\Brazil\PixKeyRepository;
use App\Repositories\Directories\Fiat\Venezuela\PagoMovilRepository;
use App\Services\Banks\BrazilBankApiService;
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
     * Columnas y keys para Brasil (PIX).
     */
    private const BR_COLUMNS = [
        'Titular',
        'Tipo PIX',
        'Clave PIX',
        'Banco',
        'Status',
    ];

    private const BR_ROW_KEYS = [
        'holder_name',
        'pix_key_type',
        'pix_key_value',
        'Bank.name',
        'status_badge',
    ];

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

    public function __construct(
        private readonly RoleUserService $roleUserService,
        private readonly BankVenezuela $bankVenezuela,
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
        private readonly PagoMovilRepository $directoryRepository,
        private readonly PixKeyRepository $pixKeyRepository,
        private readonly CountryRepositoryInterface $countryRepository,
        private readonly CurrencyService $currencyService,
        private readonly BrazilBankApiService $brazilBankApi,
    ) {
    }

    /**
     * Obtiene directorios del usuario listos para tabla Inertia según el país.
     *
     * @param int    $userId      ID del usuario
     * @param string $countryCode Código ISO2 del país (por defecto VE)
     */
    public function getAll(int $userId, string $countryCode = 'VE') : array
    {
        return $this->getDirectoriesTableData($userId, $countryCode);
    }

    /**
     * Retorna el contexto completo (bancos + documentTypes + directorios) para un país.
     * Usado por el endpoint AJAX al cambiar de tab.
     */
    public function getContextByCountry(int $userId, string $countryCode) : array
    {
        $country = $this->countryRepository
            ->findByIsoCode2($countryCode, ['id', 'name', 'iso_code_2']);

        return [
            'banks' => $this->getBanksByCountry($countryCode),
            'documentTypes' => ($country && $countryCode === 'VE')
                ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
                : collect(),
            'directories' => $this->getDirectoriesTableData($userId, $countryCode),
        ];
    }

    /**
     * Resuelve el contexto del directorio según país.
     *
     * @param int                                 $userId      ID del usuario dueño efectivo (owner — puede ser distinto del actor si un admin está gestionando en su nombre)
     * @param string                              $countryCode Código ISO2 del país (por defecto VE)
     * @param array{is_impersonation?: bool}|null $access      resultado de DirectoryAdminAccessService::resolve() — solo se usa `is_impersonation` aquí
     *
     * @return DirectoryContextDTO Contexto listo para la vista
     *
     * @throws DomainException
     */
    public function resolve(int $userId, string $countryCode = 'VE', ?array $access = null) : DirectoryContextDTO
    {
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        $country = $this->countryRepository
            ->findByIsoCode2($countryCode, ['id', 'name', 'iso_code_2']);

        $currencies = $this->currencyService->getAvailableCurrenciesWithLevel($userId);

        $isImpersonation = $access['is_impersonation'] ?? false;

        return new DirectoryContextDTO(
            banks: $this->getBanksByCountry($countryCode),
            directories: $this->getDirectoriesTableData($userId, $countryCode),
            documentTypes: ($country && $countryCode === 'VE')
                ? $this->documentTypeRepository->getActiveByCountry((int) $country->id)
                : collect(),
            roleUser: $role,
            currencies: $currencies,
            isImpersonation: $isImpersonation,
            viewingUser: $isImpersonation ? $this->resolveViewingUser($userId) : null,
        );
    }

    /**
     * Obtiene bancos según el país.
     */
    private function getBanksByCountry(string $countryCode) : Collection
    {
        if ($countryCode === 'BR') {
            return $this->brazilBankApi->getOrSync();
        }

        return $this->bankVenezuela
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
    }

    /**
     * Construye el contexto de tabla para directorios según el país:
     * - IDs hasheados
     * - Keys normalizadas
     * - Columnas para frontend
     *
     * @param int    $userId      ID del usuario
     * @param string $countryCode Código ISO2 del país
     */
    private function getDirectoriesTableData(int $userId, string $countryCode = 'VE') : array
    {
        if ($countryCode === 'BR') {
            return $this->getPixKeysTableData($userId);
        }

        return $this->getPagoMovilTableData($userId);
    }

    /**
     * Obtiene datos de tabla para Pago Móvil (Venezuela).
     */
    private function getPagoMovilTableData(int $userId) : array
    {
        $directories = $this->directoryRepository->getByUser($userId);

        return $this->processCollectionTableData(
            $directories,
            self::VE_COLUMNS,
            self::VE_ROW_KEYS,
        );
    }

    /**
     * Obtiene datos de tabla para PIX (Brasil).
     */
    private function getPixKeysTableData(int $userId) : array
    {
        $pixKeys = $this->pixKeyRepository->getByUser((string) $userId);

        return $this->processCollectionTableData(
            $pixKeys,
            self::BR_COLUMNS,
            self::BR_ROW_KEYS,
        );
    }

    /**
     * Datos mínimos del usuario dueño del directorio, para el banner "Viendo
     * el directorio de X" que la UI muestra cuando un admin está gestionando
     * el directorio de otro usuario (isImpersonation=true).
     */
    private function resolveViewingUser(int $userId) : ?array
    {
        $user = \App\Models\User::find($userId, ['id', 'username', 'email', 'first_name', 'last_name']);

        if (! $user) {
            return null;
        }

        return [
            'id' => (string) $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => trim("{$user->first_name} {$user->last_name}"),
        ];
    }
}
