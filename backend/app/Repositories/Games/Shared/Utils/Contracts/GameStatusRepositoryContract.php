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
     * Obtener estados por descripción (búsqueda exacta).
     *
     * @param string $label Descripción exacta del estado
     * @return Collection Colección de estados que coinciden
     */
    public function getByLabel(string $label): Collection;

    /**
     * Buscar estados por descripción (búsqueda parcial).
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Colección de estados que contienen el término
     */
    public function searchByLabel(string $searchTerm): Collection;

    /**
     * Obtener todos los estados como array para selects.
     *
     * Retorna un array con id => label para usar en formularios.
     *
     * @return array<string, string> Array asociativo [id => label]
     */
    public function getForSelect(): array;

    /**
     * Crear un nuevo estado de juego.
     *
     * @param string $id ID único del estado
     * @param string $label Descripción legible del estado
     * @return GameStatus El estado creado
     */
    public function createStatus(string $id, string $label): GameStatus;

    /**
     * Actualizar la descripción de un estado.
     *
     * @param string $statusid ID del estado a actualizar
     * @param string $label Nueva descripción
     * @return bool True si se actualizó correctamente
     */
    public function updateLabel(string $statusid, string $label): bool;

    /**
     * Actualizar el valor de un estado.
     *
     * @param string $statusid ID del estado a actualizar
     * @param string $id Nuevo valor único
     * @return bool True si se actualizó correctamente
     */
    public function updateId(string $statusid, string $id): bool;

    /**
     * Eliminar un estado por su ID.
     *
     * @param string $statusid ID del estado a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function deleteStatus(string $statusid): bool;

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
     * @param string $id ID a verificar
     * @param string|null $excludeid ID a excluir de la verificación (para updates)
     * @return bool True si el valor es válido y único
     */
    public function isValidUniqueId(string $id, ?string $excludeid = null): bool;

    /**
     * Obtener estados ordenados alfabéticamente por descripción.
     *
     * @return Collection Estados ordenados por descripción
     */
    public function getOrderedByLabel(): Collection;

    /**
     * Obtener estados ordenados alfabéticamente por valor.
     *
     * @return Collection Estados ordenados por valor
     */
    public function getOrderedById(): Collection;
}
