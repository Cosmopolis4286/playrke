<?php

namespace App\Repositories\Games\Common\ConnectionType;

use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\ConnectionType\ConnectionTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ConnectionTypeRepository extends BaseRepository implements ConnectionTypeRepositoryInterface
{
    public function __construct(ConnectionType $model)
    {
        $this->model = $model;
    }

    public function getActiveConnectionTypes(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    public function getInactiveConnectionTypes(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    public function getByLabel(string $label): ?ConnectionType
    {
        return $this->model->where('label', $label)->first();
    }

    public function searchByLabel(string $searchTerm): Collection
    {
        return $this->model->where('label', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function activateConnectionType($connectionTypeId): bool
    {
        return $this->model->where('id', $connectionTypeId)
            ->update(['status' => 'ACTIVE']) > 0;
    }

    public function deactivateConnectionType($connectionTypeId): bool
    {
        return $this->model->where('id', $connectionTypeId)
            ->update(['status' => 'INACTIVE']) > 0;
    }

    public function createConnectionType(string $code, string $label, bool $isActive = true): ConnectionType
    {
        return $this->model->create([
            'code' => $code,
            'label' => $label,
            'status' => $isActive ? 'ACTIVE' : 'INACTIVE'
        ]);
    }

    public function updateConnectionType($connectionTypeId, array $data): bool
    {
        return $this->model->where('id', $connectionTypeId)
            ->update($data) > 0;
    }

    public function existsByLabel(string $label): bool
    {
        return $this->model->where('label', $label)->exists();
    }

    public function getConnectionTypesWithPrizePoolTypes(): Collection
    {
        return $this->model->with('gamePrizePoolTypes')->get();
    }

    public function getConnectionTypesByStatus(bool $isActive = true): Collection
    {
        return $this->model->where('status', $isActive ? 'ACTIVE' : 'INACTIVE')->get();
    }

    public function toggleConnectionTypeStatus($connectionTypeId): bool
    {
        $connectionType = $this->model->find($connectionTypeId);

        if ($connectionType) {
            return $connectionType->update(['status' => $connectionType->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);
        }

        return false;
    }

    public function bulkActivateConnectionTypes(array $connectionTypeIds): int
    {
        return $this->model->whereIn('id', $connectionTypeIds)
            ->update(['status' => 'ACTIVE']);
    }

    public function bulkDeactivateConnectionTypes(array $connectionTypeIds): int
    {
        return $this->model->whereIn('id', $connectionTypeIds)
            ->update(['status' => 'INACTIVE']);
    }

    public function getConnectionTypeStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('status', 'INACTIVE')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    public function getRecentlyCreatedConnectionTypes(int $days = 30): Collection
    {
        $since = Carbon::now()->subDays($days);

        return $this->model->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPopularConnectionTypes(): Collection
    {
        return $this->model->withCount('gamePrizePoolTypes')
            ->orderBy('game_prize_pool_types_count', 'desc')
            ->get();
    }
}
