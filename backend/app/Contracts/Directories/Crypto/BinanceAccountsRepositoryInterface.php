<?php

namespace App\Contracts\Directories\Crypto;

use App\Models\Directories\Crypto\Binance\BinanceAccount;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de cuentas Binance
 * Define métodos específicos para operaciones con BinanceAccount
 */
interface BinanceAccountsRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener cuentas por tipo
     *
     * @param string $accountType Tipo de cuenta (E/P/B)
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getByAccountType(string $accountType, array $columns = ['*']): Collection;

    /**
     * Obtener cuentas por usuario
     *
     * @param string $userId ID del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getByUserId(string $userId, array $columns = ['*']): Collection;

    /**
     * Obtener cuentas activas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getActive(array $columns = ['*']): Collection;

    /**
     * Obtener cuentas inactivas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getInactive(array $columns = ['*']): Collection;

    /**
     * Obtener cuentas bloqueadas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getBlocked(array $columns = ['*']): Collection;

    /**
     * Verificar si existe una cuenta
     *
     * @param string $accountType Tipo de cuenta
     * @param string $dataValue Valor de los datos
     * @param string|null $excludeUserId ID de usuario a excluir de la búsqueda
     * @return bool
     */
    public function existsAccount(string $accountType, string $dataValue, ?string $excludeUserId = null): bool;

    /**
     * Buscar cuenta por valor de datos
     *
     * @param string $dataValue Valor de los datos
     * @param array $columns Columnas a seleccionar
     * @return BinanceAccount|null
     */
    public function findByDataValue(string $dataValue, array $columns = ['*']): ?BinanceAccount;

    /**
     * Buscar cuentas por valor parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function searchByDataValue(string $searchTerm, array $columns = ['*']): Collection;

    /**
     * Obtener cuentas de email
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getEmailAccounts(array $columns = ['*']): Collection;

    /**
     * Obtener cuentas de teléfono
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getPhoneAccounts(array $columns = ['*']): Collection;

    /**
     * Obtener cuentas por ID de Binance
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getBinanceIdAccounts(array $columns = ['*']): Collection;

    /**
     * Obtener cuentas activas por usuario
     *
     * @param string $userId ID del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getActiveByUserId(string $userId, array $columns = ['*']): Collection;

    /**
     * Contar cuentas por tipo
     *
     * @return array Array con conteo por tipo
     */
    public function getCountByType(): array;

    /**
     * Contar cuentas por estado
     *
     * @return array Array con conteo por estado
     */
    public function getCountByStatus(): array;

    /**
     * Obtener cuentas con usuario eager loaded
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccount>
     */
    public function getAllWithUser(array $columns = ['*']): Collection;
}
