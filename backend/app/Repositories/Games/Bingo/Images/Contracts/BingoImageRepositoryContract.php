<?php

namespace App\Repositories\Games\Bingo\Images\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Bingo\Images\BingoImage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de imágenes de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * las imágenes utilizadas en el sistema de bingo.
 *
 * @package App\Repositories\Games\Bingo\Images\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoImageRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener imágenes por tipo
     *
     * @param string $type
     * @return Collection<int, BingoImage>
     */
    public function getByType(string $type): Collection;

    /**
     * Obtener imagen por parámetro
     *
     * @param string $param
     * @return BingoImage|null
     */
    public function findByParam(string $param): ?BingoImage;

    /**
     * Obtener imagen por código
     *
     * @param string $code
     * @return BingoImage|null
     */
    public function findByCode(string $code): ?BingoImage;

    /**
     * Obtener imagen por ruta
     *
     * @param string $route
     * @return BingoImage|null
     */
    public function findByRoute(string $route): ?BingoImage;

    /**
     * Obtener imágenes activas
     *
     * @return Collection<int, BingoImage>
     */
    public function getActiveImages(): Collection;

    /**
     * Obtener imágenes inactivas
     *
     * @return Collection<int, BingoImage>
     */
    public function getInactiveImages(): Collection;

    /**
     * Obtener tipos únicos de imágenes
     *
     * @return Collection
     */
    public function getUniqueTypes(): Collection;

    /**
     * Obtener parámetros únicos
     *
     * @return Collection
     */
    public function getUniqueParams(): Collection;

    /**
     * Obtener códigos únicos
     *
     * @return Collection
     */
    public function getUniqueCodes(): Collection;

    /**
     * Verificar si existe una imagen por código
     *
     * @param string $code
     * @return bool
     */
    public function existsByCode(string $code): bool;

    /**
     * Verificar si existe una imagen por ruta
     *
     * @param string $route
     * @return bool
     */
    public function existsByRoute(string $route): bool;

    /**
     * Contar imágenes por tipo
     *
     * @param string $type
     * @return int
     */
    public function countByType(string $type): int;

    /**
     * Contar imágenes por estado
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int;

    /**
     * Obtener estadísticas de imágenes
     *
     * @return array{
     *     total_images: int,
     *     active_images: int,
     *     inactive_images: int,
     *     types_count: int,
     *     by_type: array
     * }
     */
    public function getImageStats(): array;

    /**
     * Buscar imágenes por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoImage>
     */
    public function searchImages(string $searchTerm): Collection;

    /**
     * Obtener imágenes agrupadas por tipo
     *
     * @return Collection
     */
    public function getGroupedByType(): Collection;

    /**
     * Obtener imágenes agrupadas por estado
     *
     * @return Collection
     */
    public function getGroupedByStatus(): Collection;

    /**
     * Actualizar estado de imagen
     *
     * @param string $imageId
     * @param string $newStatus
     * @return bool
     */
    public function updateImageStatus(string $imageId, string $newStatus): bool;

    /**
     * Activar imagen
     *
     * @param string $imageId
     * @return bool
     */
    public function activateImage(string $imageId): bool;

    /**
     * Desactivar imagen
     *
     * @param string $imageId
     * @return bool
     */
    public function deactivateImage(string $imageId): bool;

    /**
     * Obtener imagen por tipo y parámetro
     *
     * @param string $type
     * @param string $param
     * @return BingoImage|null
     */
    public function findByTypeAndParam(string $type, string $param): ?BingoImage;

    /**
     * Crear nueva imagen
     *
     * @param array $imageData
     * @return BingoImage
     */
    public function createImage(array $imageData): BingoImage;

    /**
     * Actualizar información de imagen
     *
     * @param string $imageId
     * @param array $updateData
     * @return bool
     */
    public function updateImage(string $imageId, array $updateData): bool;

    /**
     * Eliminar imagen
     *
     * @param string $imageId
     * @return bool
     */
    public function deleteImage(string $imageId): bool;

    /**
     * Verificar si la ruta de imagen es válida
     *
     * @param string $route
     * @return bool
     */
    public function isValidImageRoute(string $route): bool;

    /**
     * Obtener imágenes duplicadas (misma ruta)
     *
     * @return Collection<int, BingoImage>
     */
    public function getDuplicateImages(): Collection;

    /**
     * Limpiar imágenes huérfanas (sin archivo físico)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedImages(): int;

    /**
     * Obtener imágenes por extensión de archivo
     *
     * @param string $extension
     * @return Collection<int, BingoImage>
     */
    public function getByFileExtension(string $extension): Collection;

    /**
     * Validar integridad de imagen
     *
     * @param string $imageId
     * @return bool
     */
    public function validateImageIntegrity(string $imageId): bool;

    /**
     * Obtener tamaño total de imágenes
     *
     * @return int Tamaño en bytes
     */
    public function getTotalImagesSize(): int;

    /**
     * Exportar lista de imágenes
     *
     * @param string $format 'json'|'csv'|'array'
     * @return mixed
     */
    public function exportImages(string $format = 'array');

    /**
     * Obtener imágenes más utilizadas
     *
     * @param int $limit
     * @return Collection<int, BingoImage>
     */
    public function getMostUsedImages(int $limit = 10): Collection;

    /**
     * Obtener imágenes por patrón de nombre
     *
     * @param string $pattern
     * @return Collection<int, BingoImage>
     */
    public function getByNamePattern(string $pattern): Collection;
}
