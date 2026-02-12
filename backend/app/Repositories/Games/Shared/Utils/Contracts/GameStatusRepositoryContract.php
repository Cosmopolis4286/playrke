<?php

namespace App\Repositories\Shared\Utils\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de estados de juegos.
 *
 * Define los métodos específicos para gestionar los estados de juegos,
 * incluyendo operaciones de consulta, creación, actualización y eliminación.
 */
interface GameStatusRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener un estado por su valor único.
     *
     * @param string $value Valor único del estado (ej. 'ACTIVE', 'FINISHED')
     * @return GameStatus|null El estado encontrado o null si no existe
     */
    public function getByValue(string $value): ?GameStatus;

    /**
     * Verificar si existe un estado con el valor dado.
     *
     * @param string $value Valor único del estado
     * @return bool True si existe, false en caso contrario
     */
    public function existsByValue(string $value): bool;

    /**
     * Obtener estados por descripción (búsqueda exacta).
     *
     * @param string $description Descripción exacta del estado
     * @return Collection Colección de estados que coinciden
     */
    public function getByDescription(string $description): Collection;

    /**
     * Buscar estados por descripción (búsqueda parcial).
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Colección de estados que contienen el término
     */
    public function searchByDescription(string $searchTerm): Collection;

    /**
     * Obtener todos los estados como array para selects.
     *
     * Retorna un array con value => description para usar en formularios.
     *
     * @return array<string, string> Array asociativo [value => description]
     */
    public function getForSelect(): array;

    /**
     * Crear un nuevo estado de juego.
     *
     * @param string $value Valor único del estado
     * @param string $description Descripción legible del estado
     * @return GameStatus El estado creado
     */
    public function createStatus(string $value, string $description): GameStatus;

    /**
     * Actualizar la descripción de un estado.
     *
     * @param string $statusId ID del estado a actualizar
     * @param string $description Nueva descripción
     * @return bool True si se actualizó correctamente
     */
    public function updateDescription(string $statusId, string $description): bool;

    /**
     * Actualizar el valor de un estado.
     *
     * @param string $statusId ID del estado a actualizar
     * @param string $value Nuevo valor único
     * @return bool True si se actualizó correctamente
     */
    public function updateValue(string $statusId, string $value): bool;

    /**
     * Eliminar un estado por su ID.
     *
     * @param string $statusId ID del estado a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function deleteStatus(string $statusId): bool;

    /**
     * Obtener estadísticas de los estados de juegos.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getStatusStatistics(): array;

    /**
     * Obtener estados creados recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Estados creados en el período especificado
     */
    public function getRecentStatuses(int $days = 30): Collection;

    /**
     * Verificar si un valor de estado es válido y único.
     *
     * @param string $value Valor a verificar
     * @param string|null $excludeId ID a excluir de la verificación (para updates)
     * @return bool True si el valor es válido y único
     */
    public function isValidUniqueValue(string $value, ?string $excludeId = null): bool;

    /**
     * Obtener estados ordenados alfabéticamente por descripción.
     *
     * @return Collection Estados ordenados por descripción
     */
    public function getOrderedByDescription(): Collection;

    /**
     * Obtener estados ordenados alfabéticamente por valor.
     *
     * @return Collection Estados ordenados por valor
     */
    public function getOrderedByValue(): Collection;
}
