<?php

namespace App\Repositories\Shared\Historical;

use App\Models\Shared\Historical\PlayHistory;
use App\Repositories\BaseRepository;
use App\Repositories\Shared\Historical\Contracts\PlayHistoryRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Repositorio para la gestión del historial de juegos.
 *
 * Implementa las operaciones específicas para el modelo PlayHistory,
 * incluyendo consultas por usuario, juego, estadísticas y análisis temporal.
 */
class PlayHistoryRepository extends BaseRepository implements PlayHistoryRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param PlayHistory $model Instancia del modelo PlayHistory
     */
    public function __construct(PlayHistory $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByUser(string $userId, ?int $limit = null): Collection
    {
        $query = $this->model->where('user_id', $userId)
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByGame(string $gameId, ?int $limit = null): Collection
    {
        $query = $this->model->where('game_id', $gameId)
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByRoom(int $roomId, ?int $limit = null): Collection
    {
        $query = $this->model->where('room_id', $roomId)
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCurrency(string $currencyId, ?int $limit = null): Collection
    {
        $query = $this->model->where('currency_id', $currencyId)
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, ?int $limit = null): Collection
    {
        $query = $this->model->whereBetween('played_at', [$startDate, $endDate])
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndGame(string $userId, string $gameId, ?int $limit = null): Collection
    {
        $query = $this->model->where('user_id', $userId)
            ->where('game_id', $gameId)
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUserAndDateRange(string $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model->where('user_id', $userId)
            ->whereBetween('played_at', [$startDate, $endDate])
            ->orderBy('played_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getWinningPlays(?string $userId = null, ?int $limit = null): Collection
    {
        $query = $this->model->where('gain', '>', 0)
            ->orderBy('gain', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getHighGainPlays(float $minGain, ?string $userId = null, ?int $limit = null): Collection
    {
        $query = $this->model->where('gain', '>=', $minGain)
            ->orderBy('gain', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createPlayRecord(
        string $userId,
        string $gameId,
        string $currencyId,
        string $action,
        float $gain = 0.0,
        ?int $roomId = null,
        ?Carbon $playedAt = null
    ): PlayHistory {
        return $this->model->create([
            'user_id' => $userId,
            'game_id' => $gameId,
            'currency_id' => $currencyId,
            'action' => $action,
            'gain' => $gain,
            'room_id' => $roomId,
            'played_at' => $playedAt ?? now()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateGain(string $playHistoryId, float $gain): bool
    {
        return $this->model->where('id', $playHistoryId)
            ->update(['gain' => $gain]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateAction(string $playHistoryId, string $action): bool
    {
        return $this->model->where('id', $playHistoryId)
            ->update(['action' => $action]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteOldRecords(int $daysOld): int
    {
        $cutoffDate = now()->subDays($daysOld);
        return $this->model->where('played_at', '<', $cutoffDate)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByUser(string $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getGeneralStatistics(): array
    {
        $totalPlays = $this->model->count();
        $totalGains = $this->model->sum('gain');
        $averageGain = $this->model->avg('gain');
        $maxGain = $this->model->max('gain');
        $uniqueUsers = $this->model->distinct('user_id')->count();
        $uniqueGames = $this->model->distinct('game_id')->count();
        $winningPlays = $this->model->where('gain', '>', 0)->count();

        return [
            'total_plays' => $totalPlays,
            'total_gains' => round($totalGains, 2),
            'average_gain' => round($averageGain ?? 0, 2),
            'max_gain' => round($maxGain ?? 0, 2),
            'unique_users' => $uniqueUsers,
            'unique_games' => $uniqueGames,
            'winning_plays' => $winningPlays,
            'winning_percentage' => $totalPlays > 0 ? round(($winningPlays / $totalPlays) * 100, 2) : 0,
            'plays_per_user' => $uniqueUsers > 0 ? round($totalPlays / $uniqueUsers, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStatistics(string $userId): array
    {
        $totalPlays = $this->model->where('user_id', $userId)->count();
        $totalGains = $this->model->where('user_id', $userId)->sum('gain');
        $averageGain = $this->model->where('user_id', $userId)->avg('gain');
        $maxGain = $this->model->where('user_id', $userId)->max('gain');
        $winningPlays = $this->model->where('user_id', $userId)->where('gain', '>', 0)->count();
        $gamesPlayed = $this->model->where('user_id', $userId)->distinct('game_id')->count();
        $lastPlay = $this->model->where('user_id', $userId)->latest('played_at')->first();

        return [
            'total_plays' => $totalPlays,
            'total_gains' => round($totalGains, 2),
            'average_gain' => round($averageGain ?? 0, 2),
            'max_gain' => round($maxGain ?? 0, 2),
            'winning_plays' => $winningPlays,
            'winning_percentage' => $totalPlays > 0 ? round(($winningPlays / $totalPlays) * 100, 2) : 0,
            'games_played' => $gamesPlayed,
            'last_play_at' => $lastPlay?->played_at?->toDateTimeString()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getGameStatistics(string $gameId): array
    {
        $totalPlays = $this->model->where('game_id', $gameId)->count();
        $totalGains = $this->model->where('game_id', $gameId)->sum('gain');
        $averageGain = $this->model->where('game_id', $gameId)->avg('gain');
        $uniqueUsers = $this->model->where('game_id', $gameId)->distinct('user_id')->count();
        $winningPlays = $this->model->where('game_id', $gameId)->where('gain', '>', 0)->count();

        return [
            'total_plays' => $totalPlays,
            'total_gains' => round($totalGains, 2),
            'average_gain' => round($averageGain ?? 0, 2),
            'unique_users' => $uniqueUsers,
            'winning_plays' => $winningPlays,
            'winning_percentage' => $totalPlays > 0 ? round(($winningPlays / $totalPlays) * 100, 2) : 0,
            'plays_per_user' => $uniqueUsers > 0 ? round($totalPlays / $uniqueUsers, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRoomStatistics(int $roomId): array
    {
        $totalPlays = $this->model->where('room_id', $roomId)->count();
        $totalGains = $this->model->where('room_id', $roomId)->sum('gain');
        $uniqueUsers = $this->model->where('room_id', $roomId)->distinct('user_id')->count();
        $uniqueGames = $this->model->where('room_id', $roomId)->distinct('game_id')->count();

        return [
            'total_plays' => $totalPlays,
            'total_gains' => round($totalGains, 2),
            'unique_users' => $uniqueUsers,
            'unique_games' => $uniqueGames,
            'plays_per_user' => $uniqueUsers > 0 ? round($totalPlays / $uniqueUsers, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getPeriodStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalPlays = $this->model->whereBetween('played_at', [$startDate, $endDate])->count();
        $totalGains = $this->model->whereBetween('played_at', [$startDate, $endDate])->sum('gain');
        $uniqueUsers = $this->model->whereBetween('played_at', [$startDate, $endDate])
            ->distinct('user_id')->count();
        $uniqueGames = $this->model->whereBetween('played_at', [$startDate, $endDate])
            ->distinct('game_id')->count();

        $days = $startDate->diffInDays($endDate) + 1;

        return [
            'total_plays' => $totalPlays,
            'total_gains' => round($totalGains, 2),
            'unique_users' => $uniqueUsers,
            'unique_games' => $uniqueGames,
            'plays_per_day' => $days > 0 ? round($totalPlays / $days, 2) : 0,
            'gains_per_day' => $days > 0 ? round($totalGains / $days, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTopPlayersByGain(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = $this->model->selectRaw('user_id, SUM(gain) as total_gain, COUNT(*) as total_plays')
            ->groupBy('user_id')
            ->orderBy('total_gain', 'desc')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->whereBetween('played_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getPopularGames(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = $this->model->selectRaw('game_id, COUNT(*) as play_count, COUNT(DISTINCT user_id) as unique_users')
            ->groupBy('game_id')
            ->orderBy('play_count', 'desc')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->whereBetween('played_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityByHour(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->model->selectRaw('HOUR(played_at) as hour, COUNT(*) as play_count')
            ->groupBy('hour')
            ->orderBy('hour');

        if ($startDate && $endDate) {
            $query->whereBetween('played_at', [$startDate, $endDate]);
        }

        $results = $query->get()->pluck('play_count', 'hour')->toArray();

        // Llenar horas faltantes con 0
        $activity = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $activity[$hour] = $results[$hour] ?? 0;
        }

        return $activity;
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityByDayOfWeek(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->model->selectRaw('DAYOFWEEK(played_at) as day_of_week, COUNT(*) as play_count')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week');

        if ($startDate && $endDate) {
            $query->whereBetween('played_at', [$startDate, $endDate]);
        }

        $results = $query->get()->pluck('play_count', 'day_of_week')->toArray();

        // Llenar días faltantes con 0 (1=Domingo, 7=Sábado)
        $activity = [];
        for ($day = 1; $day <= 7; $day++) {
            $activity[$day] = $results[$day] ?? 0;
        }

        return $activity;
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentActivity(int $hours = 24, ?int $limit = null): Collection
    {
        $cutoffTime = now()->subHours($hours);
        $query = $this->model->where('played_at', '>=', $cutoffTime)
            ->orderBy('played_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByAction(string $action, ?string $userId = null, ?int $limit = null): Collection
    {
        $query = $this->model->where('action', 'LIKE', "%{$action}%")
            ->orderBy('played_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getDailySummary(Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return $this->getPeriodStatistics($startOfDay, $endOfDay);
    }

    /**
     * {@inheritDoc}
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        return $this->getPeriodStatistics($startOfMonth, $endOfMonth);
    }

    /**
     * {@inheritDoc}
     */
    public function hasUserPlayedGame(string $userId, string $gameId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('game_id', $gameId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getLastPlayByUser(string $userId): ?PlayHistory
    {
        return $this->model->where('user_id', $userId)
            ->latest('played_at')
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalGainsByUser(string $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = $this->model->where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('played_at', [$startDate, $endDate]);
        }

        return (float) $query->sum('gain');
    }

    /**
     * {@inheritDoc}
     */
    public function countPlaysByUser(string $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $query = $this->model->where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('played_at', [$startDate, $endDate]);
        }

        return $query->count();
    }
}