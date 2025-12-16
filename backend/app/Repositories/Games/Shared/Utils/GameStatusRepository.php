<?php

namespace App\Repositories\Games\Shared\Utils;

use App\Models\Games\Shared\Utils\GameStatus;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Shared\Utils\Contracts\GameStatusRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de estados de juegos.
 *
 * Implementa las operaciones específicas para el modelo GameStatus,
 * incluyendo consultas por valor, descripción y operaciones CRUD completas.
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
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByValue(string $value): ?GameStatus
    {
        return $this->model->where('value', $value)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByValue(string $value): bool
    {
        return $this->model->where('value', $value)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDescription(string $description): Collection
    {
        return $this->model->where('description', $description)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(): array
    {
        return $this->model->orderBy('description')->pluck('description', 'value')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function createStatus(string $value, string $description): GameStatus
    {
        return $this->model->create([
            'value' => $value,
            'description' => $description
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateDescription(string $statusId, string $description): bool
    {
        return $this->model->where('id', $statusId)
            ->update(['description' => $description]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateValue(string $statusId, string $value): bool
    {
        return $this->model->where('id', $statusId)
            ->update(['value' => $value]) > 0;
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
        $averageDescriptionLength = $this->model->avg(DB::raw('LENGTH(description)'));
        $averageValueLength = $this->model->avg(DB::raw('LENGTH(value)'));
        $longestDescription = $this->model->orderByRaw('LENGTH(description) DESC')->first();
        $shortestDescription = $this->model->orderByRaw('LENGTH(description) ASC')->first();

        return [
            'total_statuses' => $total,
            'average_description_length' => round($averageDescriptionLength ?? 0, 2),
            'average_value_length' => round($averageValueLength ?? 0, 2),
            'longest_description' => $longestDescription?->description ?? '',
            'shortest_description' => $shortestDescription?->description ?? '',
            'longest_description_length' => $longestDescription ? strlen($longestDescription->description) : 0,
            'shortest_description_length' => $shortestDescription ? strlen($shortestDescription->description) : 0
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
    public function isValidUniqueValue(string $value, ?string $excludeId = null): bool
    {
        $query = $this->model->where('value', $value);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByDescription(): Collection
    {
        return $this->model->orderBy('description')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByValue(): Collection
    {
        return $this->model->orderBy('value')->get();
    }
}
