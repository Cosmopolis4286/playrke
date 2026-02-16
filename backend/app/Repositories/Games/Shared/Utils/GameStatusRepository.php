<?php

namespace App\Repositories\Shared\Utils;

use App\Models\Games\Common\Utils\GameStatus;
use App\Repositories\BaseRepository;
use App\Repositories\Shared\Utils\Contracts\GameStatusRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de estados de juegos.
 *
 * Implementa las operaciones específicas para el modelo GameStatus,
 * incluyendo consultas por valor, etiqueta y operaciones CRUD completas.
 */
class GameStatusRepository extends BaseRepository implements GameStatusRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param GameStatus $model Instancia del modelo GameStatus
     */
    public function __construct(GameStatus $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByLabel(string $label): Collection
    {
        return $this->model->where('label', $label)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByLabel(string $searchTerm): Collection
    {
        return $this->model->where('label', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(): array
    {
        return $this->model->orderBy('label')->pluck('label', 'id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function createStatus(string $id, string $label): GameStatus
    {
        return $this->model->create([
            'id' => $id,
            'label' => $label
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateLabel(string $statusId, string $label): bool
    {
        return $this->model->where('id', $statusId)
            ->update(['label' => $label]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateId(string $statusId, string $id): bool
    {
        return $this->model->where('id', $statusId)
            ->update(['id' => $id]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteStatus(string $statusId): bool
    {
        return $this->model->where('id', $statusId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusStatistics(): array
    {
        $total = $this->model->count();
        $averageLabelLength = $this->model->avg(DB::raw('LENGTH(label)'));
        $averageidLength = $this->model->avg(DB::raw('LENGTH(id)'));
        $longestLabel = $this->model->orderByRaw('LENGTH(label) DESC')->first();
        $shortestLabel = $this->model->orderByRaw('LENGTH(label) ASC')->first();

        return [
            'total_statuses' => $total,
            'average_label_length' => round($averageLabelLength ?? 0, 2),
            'average_id_length' => round($averageIdLength ?? 0, 2),
            'longest_label' => $longestLabel?->label ?? '',
            'shortest_label' => $shortestLabel?->label ?? '',
            'longest_label_length' => $longestLabel ? strlen($longestLabel->label) : 0,
            'shortest_label_length' => $shortestLabel ? strlen($shortestLabel->label) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentStatuses(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function isValidUniqueId(string $id, ?string $excludeId = null): bool
    {
        $query = $this->model->where('id', $id);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByLabel(): Collection
    {
        return $this->model->orderBy('label')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedById(): Collection
    {
        return $this->model->orderBy('id')->get();
    }
}
