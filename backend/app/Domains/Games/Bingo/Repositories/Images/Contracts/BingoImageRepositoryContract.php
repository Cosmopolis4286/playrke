<?php

namespace App\Domains\Games\Bingo\Repositories\Images\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Image\BingoImage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de imágenes de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * las imágenes utilizadas en el sistema de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
interface BingoImageRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Activar imagen
     */
    public function activateImage(string $imageId) : bool;

    /**
     * Limpiar imágenes huérfanas (sin archivo físico)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedImages() : int;

    /**
     * Contar imágenes por estado
     */
    public function countByStatus(string $status) : int;

    /**
     * Contar imágenes por tipo
     */
    public function countByType(string $type) : int;

    /**
     * Crear nueva imagen
     */
    public function createImage(array $imageData) : BingoImage;

    /**
     * Desactivar imagen
     */
    public function deactivateImage(string $imageId) : bool;

    /**
     * Eliminar imagen
     */
    public function deleteImage(string $imageId) : bool;

    /**
     * Verificar si existe una imagen por código
     */
    public function existsByCode(string $code) : bool;

    /**
     * Verificar si existe una imagen por ruta
     */
    public function existsByRoute(string $route) : bool;

    /**
     * Exportar lista de imágenes
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportImages(string $format = 'array');

    /**
     * Obtener imagen por código
     */
    public function findByCode(string $code) : ?BingoImage;

    /**
     * Obtener imagen por parámetro
     */
    public function findByParam(string $param) : ?BingoImage;

    /**
     * Obtener imagen por ruta
     */
    public function findByRoute(string $route) : ?BingoImage;

    /**
     * Obtener imagen por tipo y parámetro
     */
    public function findByTypeAndParam(string $type, string $param) : ?BingoImage;

    /**
     * Obtener imágenes activas
     *
     * @return Collection<int, BingoImage>
     */
    public function getActiveImages() : Collection;

    /**
     * Obtener imágenes por extensión de archivo
     *
     * @return Collection<int, BingoImage>
     */
    public function getByFileExtension(string $extension) : Collection;

    /**
     * Obtener imágenes por patrón de nombre
     *
     * @return Collection<int, BingoImage>
     */
    public function getByNamePattern(string $pattern) : Collection;

    /**
     * Obtener imágenes por tipo
     *
     * @return Collection<int, BingoImage>
     */
    public function getByType(string $type) : Collection;

    /**
     * Obtener imágenes duplicadas (misma ruta)
     *
     * @return Collection<int, BingoImage>
     */
    public function getDuplicateImages() : Collection;

    /**
     * Obtener imágenes agrupadas por estado
     */
    public function getGroupedByStatus() : Collection;

    /**
     * Obtener imágenes agrupadas por tipo
     */
    public function getGroupedByType() : Collection;

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
    public function getImageStats() : array;

    /**
     * Obtener imágenes inactivas
     *
     * @return Collection<int, BingoImage>
     */
    public function getInactiveImages() : Collection;

    /**
     * Obtener imágenes más utilizadas
     *
     * @return Collection<int, BingoImage>
     */
    public function getMostUsedImages(int $limit = 10) : Collection;

    /**
     * Obtener tamaño total de imágenes
     *
     * @return int Tamaño en bytes
     */
    public function getTotalImagesSize() : int;

    /**
     * Obtener códigos únicos
     */
    public function getUniqueCodes() : Collection;

    /**
     * Obtener parámetros únicos
     */
    public function getUniqueParams() : Collection;

    /**
     * Obtener tipos únicos de imágenes
     */
    public function getUniqueTypes() : Collection;

    /**
     * Verificar si la ruta de imagen es válida
     */
    public function isValidImageRoute(string $route) : bool;

    /**
     * Buscar imágenes por término de búsqueda
     *
     * @return Collection<int, BingoImage>
     */
    public function searchImages(string $searchTerm) : Collection;

    /**
     * Actualizar información de imagen
     */
    public function updateImage(string $imageId, array $updateData) : bool;

    /**
     * Actualizar estado de imagen
     */
    public function updateImageStatus(string $imageId, string $newStatus) : bool;

    /**
     * Validar integridad de imagen
     */
    public function validateImageIntegrity(string $imageId) : bool;
}
