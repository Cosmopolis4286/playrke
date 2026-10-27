<?php

namespace App\Repositories\Games\Common\Room;

use App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface;
use App\Models\Games\Common\Room\RoomType;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Repositorio para la gestión de tipos de sala (RoomType).
 *
 * Implementa las operaciones específicas para el modelo RoomType,
 * incluyendo gestión de estados, búsquedas y estadísticas.
 */
class RoomTypeRepository extends BaseRepository implements RoomTypeRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param RoomType $model Instancia del modelo RoomType
     */
    public function __construct(RoomType $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function activateType(string $typeId) : bool
    {
        return $this->updateStatus($typeId, 'ACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $typeIds, string $status) : int
    {
        return $this->model->whereIn('id', $typeIds)
            ->update(['status' => $status]);
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkTypes(array $labels, string $status = 'ACTIVE') : Collection
    {
        $types = collect();

        foreach ($labels as $label) {
            $normalizedLabel = $this->normalizeLabel($label);

            // Verificar si ya existe antes de crear
            if (! $this->existsByLabel($normalizedLabel)) {
                $type = $this->createType(Str::upper(Str::slug($normalizedLabel, '_')), $normalizedLabel, $status);
                $types->push($type);
            }
        }

        return $types;
    }

    /**
     * {@inheritDoc}
     */
    public function createType(string $code, string $label, string $status = 'ACTIVE') : RoomType
    {
        return $this->model->create([
            'code' => $code,
            'label' => $this->normalizeLabel($label),
            'status' => $status,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateType(string $typeId) : bool
    {
        return $this->updateStatus($typeId, 'INACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteType(string $typeId) : bool
    {
        return $this->model->where('id', $typeId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function existsByLabel(string $label, ?string $excludeId = null) : bool
    {
        $query = $this->model->where('label', $label);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveTypes() : Collection
    {
        return $this->getByStatus('ACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function getByLabel(string $label) : ?RoomType
    {
        return $this->model->where('label', $label)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(bool $activeOnly = true) : array
    {
        $query = $this->model->orderBy('label');

        if ($activeOnly) {
            $query->where('status', 'ACTIVE');
        }

        return $query->pluck('label', 'id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveTypes() : Collection
    {
        return $this->getByStatus('INACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function getLabelSuggestions(string $partialText, int $limit = 5) : Collection
    {
        return $this->model->where('label', 'LIKE', "%{$partialText}%")
            ->where('status', 'ACTIVE')
            ->orderBy('label')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByLabel(bool $activeOnly = false) : Collection
    {
        $query = $this->model->orderBy('label');

        if ($activeOnly) {
            $query->where('status', 'ACTIVE');
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentTypes(int $days = 30) : Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeStatistics() : array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('status', 'INACTIVE')->count();

        return [
            'total_types' => $total,
            'active_types' => $active,
            'inactive_types' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function isValidLabel(string $label) : bool
    {
        $normalized = $this->normalizeLabel($label);

        // Verificar longitud
        if (strlen($normalized) < 2 || strlen($normalized) > 50) {
            return false;
        }

        // Verificar caracteres válidos (letras, números, espacios, guiones)
        return ! (! preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $normalized));
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeLabel(string $label) : string
    {
        // Limpiar espacios extra
        $normalized = trim($label);

        // Convertir a título (primera letra de cada palabra en mayúscula)
        $normalized = Str::title($normalized);

        // Reemplazar múltiples espacios con uno solo
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * {@inheritDoc}
     */
    public function searchByLabel(string $searchTerm) : Collection
    {
        return $this->model->where('label', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function updateLabel(string $typeId, string $label) : bool
    {
        return $this->model->where('id', $typeId)
            ->update(['label' => $this->normalizeLabel($label)]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $typeId, string $status) : bool
    {
        return $this->model->where('id', $typeId)
            ->update(['status' => $status]) > 0;
    }
}
