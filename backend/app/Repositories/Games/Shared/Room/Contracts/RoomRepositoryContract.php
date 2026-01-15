<?php

namespace App\Repositories\Games\Shared\Room\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Shared\Room\Room;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de salas de juego.
 *
 * Define los métodos específicos para gestionar las salas del sistema,
 * incluyendo operaciones de consulta, creación, tipos de sala y estadísticas.
 */
interface RoomRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener todas las salas por tipo.
     *
     * @param string $type Tipo de sala (PV|PB)
     * @return Collection Colección de salas del tipo especificado
     */
    public function getByType(string $type): Collection;

    /**
     * Obtener todas las salas privadas.
     *
     * @return Collection Colección de salas privadas
     */
    public function getPrivateRooms(): Collection;

    /**
     * Obtener todas las salas públicas.
     *
     * @return Collection Colección de salas públicas
     */
    public function getPublicRooms(): Collection;

    /**
     * Verificar si un tipo de sala es válido.
     *
     * @param string $type Tipo a verificar
     * @return bool True si es válido, false en caso contrario
     */
    public function isValidRoomType(string $type): bool;

    /**
     * Obtener todos los tipos de sala disponibles.
     *
     * @param string|null $excludeType Tipo a excluir (opcional)
     * @return array<string, string> Array con tipos de sala [código => descripción]
     */
    public function getRoomTypes(?string $excludeType = null): array;

    /**
     * Obtener la descripción de un tipo de sala.
     *
     * @param string $type Código del tipo de sala
     * @return string|null Descripción del tipo o null si no existe
     */
    public function getRoomTypeDescription(string $type): ?string;

    /**
     * Crear una nueva sala.
     *
     * @param string $type Tipo de sala (PV|PB)
     * @param array $additionalData Datos adicionales de la sala
     * @return Room La sala creada
     */
    public function createRoom(string $type, array $additionalData = []): Room;

    /**
     * Actualizar el tipo de una sala.
     *
     * @param string $roomId ID de la sala
     * @param string $type Nuevo tipo de sala
     * @return bool True si se actualizó correctamente
     */
    public function updateRoomType(string $roomId, string $type): bool;

    /**
     * Obtener estadísticas de tipos de sala.
     *
     * @return array<string, mixed> Estadísticas por tipo de sala
     */
    public function getRoomTypeStatistics(): array;

    /**
     * Contar salas por tipo.
     *
     * @param string $type Tipo de sala
     * @return int Número de salas del tipo especificado
     */
    public function countByType(string $type): int;

    /**
     * Obtener salas ordenadas por tipo.
     *
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return Collection Salas ordenadas por tipo
     */
    public function getOrderedByType(string $direction = 'asc'): Collection;

    /**
     * Buscar salas por múltiples tipos.
     *
     * @param array $types Array de tipos de sala
     * @return Collection Salas que coinciden con los tipos especificados
     */
    public function getByMultipleTypes(array $types): Collection;

    /**
     * Verificar si existe al menos una sala de un tipo específico.
     *
     * @param string $type Tipo de sala
     * @return bool True si existe al menos una sala del tipo
     */
    public function existsRoomOfType(string $type): bool;

    /**
     * Obtener salas para uso en selects/formularios.
     *
     * @param string|null $filterType Filtrar por tipo específico (opcional)
     * @return array<string, string> Array asociativo [id => descripción]
     */
    public function getForSelect(?string $filterType = null): array;

    /**
     * Validar datos de sala antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @return array<string, string> Array de errores de validación (vacío si es válido)
     */
    public function validateRoomData(array $data): array;

    /**
     * Obtener el tipo más común de sala.
     *
     * @return string|null Tipo más común o null si no hay salas
     */
    public function getMostCommonRoomType(): ?string;

    /**
     * Obtener el tipo menos común de sala.
     *
     * @return string|null Tipo menos común o null si no hay salas
     */
    public function getLeastCommonRoomType(): ?string;

    /**
     * Crear salas por lotes con diferentes tipos.
     *
     * @param array $roomsData Array de datos de salas (cada elemento debe incluir 'type')
     * @return Collection Salas creadas
     */
    public function createBulkRooms(array $roomsData): Collection;

    /**
     * Obtener distribución porcentual de tipos de sala.
     *
     * @return array<string, float> Porcentaje de cada tipo de sala
     */
    public function getRoomTypeDistribution(): array;

    /**
     * Cambiar masivamente el tipo de múltiples salas.
     *
     * @param array $roomIds IDs de las salas
     * @param string $newType Nuevo tipo de sala
     * @return int Número de salas actualizadas
     */
    public function bulkUpdateRoomType(array $roomIds, string $newType): int;

    /**
     * Obtener salas agrupadas por tipo.
     *
     * @return array<string, Collection> Array con salas agrupadas por tipo
     */
    public function getGroupedByType(): array;
}