<?php

namespace App\Repositories\Shared\Utils;

use App\Models\Shared\Utils\GameSound;
use App\Repositories\BaseRepository;
use App\Repositories\Shared\Utils\Contracts\GameSoundRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class GameSoundRepository extends BaseRepository implements GameSoundRepositoryContract
{
    public function __construct(GameSound $model)
    {
        parent::__construct($model);
    }

    public function getActiveSounds(): Collection
    {
        return $this->model->where('status', true)->get();
    }

    public function getInactiveSounds(): Collection
    {
        return $this->model->where('status', false)->get();
    }

    public function getSoundsByStatus(bool $isActive): Collection
    {
        return $this->model->where('status', $isActive)->get();
    }

    public function getByFile(string $file): ?GameSound
    {
        return $this->model->where('file', $file)->first();
    }

    public function existsFile(string $file): bool
    {
        return $this->model->where('file', $file)->exists();
    }

    public function getSoundsByDescription(string $description): Collection
    {
        return $this->model->where('description', $description)->get();
    }

    public function searchSoundsByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")
            ->where('status', true)
            ->get();
    }

    public function createSound(string $file, ?string $description = null, bool $isActive = true): GameSound
    {
        return $this->model->create([
            'file' => $file,
            'description' => $description,
            'status' => $isActive
        ]);
    }

    public function updateSoundStatus($soundId, bool $isActive): bool
    {
        return $this->model->where('id', $soundId)
            ->update(['status' => $isActive]) > 0;
    }

    public function activateSound($soundId): bool
    {
        return $this->updateSoundStatus($soundId, true);
    }

    public function deactivateSound($soundId): bool
    {
        return $this->updateSoundStatus($soundId, false);
    }

    public function updateSoundDescription($soundId, ?string $description): bool
    {
        return $this->model->where('id', $soundId)
            ->update(['description' => $description]) > 0;
    }

    public function updateSoundFile($soundId, string $file): bool
    {
        return $this->model->where('id', $soundId)
            ->update(['file' => $file]) > 0;
    }

    public function deleteSound($soundId): bool
    {
        return $this->model->where('id', $soundId)->delete() > 0;
    }

    public function bulkUpdateStatus(array $soundIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $soundIds)
            ->update(['status' => $isActive]);
    }

    public function getSoundStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $withDescription = $this->model->whereNotNull('description')->count();
        $withoutDescription = $this->model->whereNull('description')->count();

        return [
            'total_sounds' => $total,
            'active_sounds' => $active,
            'inactive_sounds' => $inactive,
            'sounds_with_description' => $withDescription,
            'sounds_without_description' => $withoutDescription,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'description_percentage' => $total > 0 ? round(($withDescription / $total) * 100, 2) : 0
        ];
    }

    public function getRecentSounds(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDuplicateFiles(): Collection
    {
        return $this->model->select('file')
            ->groupBy('file')
            ->havingRaw('COUNT(*) > 1')
            ->get();
    }

    public function getSoundsWithoutDescription(): Collection
    {
        return $this->model->whereNull('description')
            ->orWhere('description', '')
            ->get();
    }

    public function getSoundsWithDescription(): Collection
    {
        return $this->model->whereNotNull('description')
            ->where('description', '!=', '')
            ->get();
    }

    public function generateUniqueFileName(string $baseName, string $extension): string
    {
        $counter = 1;
        $fileName = $baseName . '.' . $extension;

        while ($this->existsFile($fileName)) {
            $fileName = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $fileName;
    }

    public function getSoundsByFileExtension(string $extension): Collection
    {
        return $this->model->where('file', 'LIKE', "%.{$extension}")
            ->get();
    }

    public function getPopularSounds(int $limit = 10): Collection
    {
        return $this->model->where('status', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}