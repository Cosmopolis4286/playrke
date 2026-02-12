<?php

namespace App\Repositories\Shared\Utils\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\Utils\GameSound;
use Illuminate\Database\Eloquent\Collection;

interface GameSoundRepositoryContract extends BaseRepositoryInterface
{
    public function getActiveSounds(): Collection;

    public function getInactiveSounds(): Collection;

    public function getSoundsByStatus(bool $isActive): Collection;

    public function getByFile(string $file): ?GameSound;

    public function existsFile(string $file): bool;

    public function getSoundsByDescription(string $description): Collection;

    public function searchSoundsByDescription(string $searchTerm): Collection;

    public function createSound(string $file, ?string $description = null, bool $isActive = true): GameSound;

    public function updateSoundStatus($soundId, bool $isActive): bool;

    public function activateSound($soundId): bool;

    public function deactivateSound($soundId): bool;

    public function updateSoundDescription($soundId, ?string $description): bool;

    public function updateSoundFile($soundId, string $file): bool;

    public function deleteSound($soundId): bool;

    public function bulkUpdateStatus(array $soundIds, bool $isActive): int;

    public function getSoundStatistics(): array;

    public function getRecentSounds(int $days = 30): Collection;

    public function getDuplicateFiles(): Collection;

    public function getSoundsWithoutDescription(): Collection;

    public function getSoundsWithDescription(): Collection;

    public function generateUniqueFileName(string $baseName, string $extension): string;

    public function getSoundsByFileExtension(string $extension): Collection;

    public function getPopularSounds(int $limit = 10): Collection;
}
