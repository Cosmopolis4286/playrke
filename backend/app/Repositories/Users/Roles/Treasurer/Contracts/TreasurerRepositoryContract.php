<?php

namespace App\Repositories\Users\Roles\Treasurer\Contracts;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de tesoreros.
 *
 * Define las operaciones específicas para gestionar los tesoreros
 * del sistema, incluyendo consultas por país, moneda, estado
 * y gestión de información financiera.
 */
interface TreasurerRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene tesoreros por país.
     *
     * @param string $countryId ID del país
     * @return Collection<Treasurer> Tesoreros del país
     */
    public function getByCountry(string $countryId): Collection;

    /**
     * Obtiene tesoreros por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return Collection<Treasurer> Tesoreros de la moneda
     */
    public function getByCurrency(string $currencyId): Collection;

    /**
     * Obtiene un tesorero por código UUID.
     *
     * @param string $uuidCode Código UUID
     * @return mixed Tesorero encontrado o null
     */
    public function getByUuidCode(string $uuidCode): mixed;

    /**
     * Crea un nuevo tesorero con validación.
     *
     * @param array $data Datos del tesorero
     * @return mixed Tesorero creado
     */
    public function createTreasurer(array $data): mixed;

    /**
     * Actualiza el estado de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param bool $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $treasurerId, bool $status): bool;

    /**
     * Valida los datos de un tesorero.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir de validaciones
     * @return array Array de errores de validación
     */
    public function validateTreasurerData(array $data, ?string $excludeId = null): array;
}
