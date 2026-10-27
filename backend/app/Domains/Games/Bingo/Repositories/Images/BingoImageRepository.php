<?php

namespace App\Domains\Games\Bingo\Repositories\Images;

use App\Domains\Games\Bingo\Models\Image\BingoImage;
use App\Domains\Games\Bingo\Repositories\Images\Contracts\BingoImageRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

/**
 * Repositorio para gestionar imágenes de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para las imágenes utilizadas en el sistema de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
class BingoImageRepository extends BaseRepository implements BingoImageRepositoryContract
{
    /**
     * Estados de imágenes
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Constructor del repositorio
     */
    public function __construct(BingoImage $model)
    {
        $this->model = $model;
    }

    /**
     * Activar imagen
     */
    public function activateImage(string $imageId) : bool
    {
        return $this->updateImageStatus($imageId, self::STATUS_ACTIVE);
    }

    /**
     * Limpiar imágenes huérfanas (sin archivo físico)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedImages() : int
    {
        $orphanedCount = 0;
        $images = $this->getAll();

        foreach ($images as $image) {
            if ($image->route && ! File::exists(public_path($image->route))) {
                $this->delete($image->id);
                $orphanedCount++;
            }
        }

        return $orphanedCount;
    }

    /**
     * Contar imágenes por estado
     */
    public function countByStatus(string $status) : int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Contar imágenes por tipo
     */
    public function countByType(string $type) : int
    {
        return $this->model->where('type', $type)->count();
    }

    /**
     * Crear nueva imagen
     */
    public function createImage(array $imageData) : BingoImage
    {
        return $this->create($imageData);
    }

    /**
     * Desactivar imagen
     */
    public function deactivateImage(string $imageId) : bool
    {
        return $this->updateImageStatus($imageId, self::STATUS_INACTIVE);
    }

    /**
     * Eliminar imagen
     */
    public function deleteImage(string $imageId) : bool
    {
        $image = $this->find($imageId);

        if (! $image) {
            return false;
        }

        // Eliminar archivo físico si existe
        if ($image->route && File::exists(public_path($image->route))) {
            File::delete(public_path($image->route));
        }

        return $this->delete($imageId);
    }

    /**
     * Verificar si existe una imagen por código
     */
    public function existsByCode(string $code) : bool
    {
        return $this->model->where('code', $code)->exists();
    }

    /**
     * Verificar si existe una imagen por ruta
     */
    public function existsByRoute(string $route) : bool
    {
        return $this->model->where('route', $route)->exists();
    }

    /**
     * Exportar lista de imágenes
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportImages(string $format = 'array')
    {
        $images = $this->getAll();

        switch ($format) {
            case 'json':
                return $images->toJson();
            case 'csv':
                // Implementar exportación CSV si es necesario
                return $images->toArray();
            case 'array':
            default:
                return $images->toArray();
        }
    }

    /**
     * Obtener imagen por código
     */
    public function findByCode(string $code) : ?BingoImage
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Obtener imagen por parámetro
     */
    public function findByParam(string $param) : ?BingoImage
    {
        return $this->model->where('param', $param)->first();
    }

    /**
     * Obtener imagen por ruta
     */
    public function findByRoute(string $route) : ?BingoImage
    {
        return $this->model->where('route', $route)->first();
    }

    /**
     * Obtener imagen por tipo y parámetro
     */
    public function findByTypeAndParam(string $type, string $param) : ?BingoImage
    {
        return $this->model->where('type', $type)
            ->where('param', $param)
            ->first();
    }

    /**
     * Obtener imágenes activas
     *
     * @return Collection<int, BingoImage>
     */
    public function getActiveImages() : Collection
    {
        return $this->getByStatus(self::STATUS_ACTIVE);
    }

    /**
     * Obtener imágenes por extensión de archivo
     *
     * @return Collection<int, BingoImage>
     */
    public function getByFileExtension(string $extension) : Collection
    {
        return $this->model->where('route', 'like', "%.{$extension}")->get();
    }

    /**
     * Obtener imágenes por patrón de nombre
     *
     * @return Collection<int, BingoImage>
     */
    public function getByNamePattern(string $pattern) : Collection
    {
        return $this->model->where('route', 'like', "%{$pattern}%")->get();
    }

    /**
     * Obtener imágenes por tipo
     *
     * @return Collection<int, BingoImage>
     */
    public function getByType(string $type) : Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Obtener imágenes duplicadas (misma ruta)
     *
     * @return Collection<int, BingoImage>
     */
    public function getDuplicateImages() : Collection
    {
        return $this->model->select('route')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('route')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Obtener imágenes agrupadas por estado
     */
    public function getGroupedByStatus() : Collection
    {
        return $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get();
    }

    /**
     * Obtener imágenes agrupadas por tipo
     */
    public function getGroupedByType() : Collection
    {
        return $this->model->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('type')
            ->get();
    }

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
    public function getImageStats() : array
    {
        $totalImages = $this->model->count();
        $activeImages = $this->countByStatus(self::STATUS_ACTIVE);
        $inactiveImages = $this->countByStatus(self::STATUS_INACTIVE);
        $typesCount = $this->getUniqueTypes()->count();

        $byType = $this->model->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total_images' => $totalImages,
            'active_images' => $activeImages,
            'inactive_images' => $inactiveImages,
            'types_count' => $typesCount,
            'by_type' => $byType,
        ];
    }

    /**
     * Obtener imágenes inactivas
     *
     * @return Collection<int, BingoImage>
     */
    public function getInactiveImages() : Collection
    {
        return $this->getByStatus(self::STATUS_INACTIVE);
    }

    /**
     * Obtener imágenes más utilizadas
     *
     * @return Collection<int, BingoImage>
     */
    public function getMostUsedImages(int $limit = 10) : Collection
    {
        // Esta implementación asume que hay un campo usage_count
        // o se puede implementar con joins a otras tablas
        return $this->model->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener tamaño total de imágenes
     *
     * @return int Tamaño en bytes
     */
    public function getTotalImagesSize() : int
    {
        $totalSize = 0;
        $images = $this->getAll();

        foreach ($images as $image) {
            if ($image->route && File::exists(public_path($image->route))) {
                $totalSize += File::size(public_path($image->route));
            }
        }

        return $totalSize;
    }

    /**
     * Obtener códigos únicos
     */
    public function getUniqueCodes() : Collection
    {
        return $this->model->distinct()->pluck('code');
    }

    /**
     * Obtener parámetros únicos
     */
    public function getUniqueParams() : Collection
    {
        return $this->model->distinct()->pluck('param');
    }

    /**
     * Obtener tipos únicos de imágenes
     */
    public function getUniqueTypes() : Collection
    {
        return $this->model->distinct()->pluck('type');
    }

    /**
     * Verificar si la ruta de imagen es válida
     */
    public function isValidImageRoute(string $route) : bool
    {
        // Verificar extensiones válidas
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        $extension = strtolower(pathinfo($route, PATHINFO_EXTENSION));

        if (! in_array($extension, $validExtensions)) {
            return false;
        }

        // Verificar si el archivo existe
        return File::exists(public_path($route));
    }

    /**
     * Buscar imágenes por término de búsqueda
     *
     * @return Collection<int, BingoImage>
     */
    public function searchImages(string $searchTerm) : Collection
    {
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('type', 'like', "%{$searchTerm}%")
                ->orWhere('param', 'like', "%{$searchTerm}%")
                ->orWhere('route', 'like', "%{$searchTerm}%")
                ->orWhere('code', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Actualizar información de imagen
     */
    public function updateImage(string $imageId, array $updateData) : bool
    {
        return $this->model->where('id', $imageId)
            ->update($updateData) > 0;
    }

    /**
     * Actualizar estado de imagen
     */
    public function updateImageStatus(string $imageId, string $newStatus) : bool
    {
        return $this->model->where('id', $imageId)
            ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Validar integridad de imagen
     */
    public function validateImageIntegrity(string $imageId) : bool
    {
        $image = $this->find($imageId);

        if (! $image) {
            return false;
        }

        // Validar campos requeridos
        if (empty($image->type) || empty($image->route)) {
            return false;
        }

        // Validar que el archivo exista
        return $this->isValidImageRoute($image->route);
    }
}
