<?php

namespace App\Domains\Platform\WinEvent\Services;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Ranking semanal de mayores ganadores en dinero — agrega platform_win_events
 * por usuario para responder "quién ganó más esta semana", a diferencia del
 * ticker (eventos individuales) o de WinEventService (registrar un evento).
 */
final class WinEventLeaderboardService
{
    private const WINDOW_DAYS = 7;
    private const DEFAULT_LIMIT = 10;

    /**
     * @return Collection<int, array{rank: int, winner_name: string, total_amount: float, wins_count: int}>
     */
    public function weeklyTopWinners(int $limit = self::DEFAULT_LIMIT): Collection
    {
        $rows = WinEvent::query()
            ->selectRaw('user_id, SUM(amount_usd) as total_amount, COUNT(*) as wins_count')
            ->where('prize_kind', WinEvent::PRIZE_MONEY)
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->groupBy('user_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->select('id', 'first_name', 'last_name', 'username')
            ->whereIn('id', $rows->pluck('user_id'))
            ->get()
            ->keyBy('id');

        return $rows->values()->map(function ($row, int $index) use ($users) {
            $user = $users->get($row->user_id);
            $winEvent = new WinEvent(['user_id' => $row->user_id]);
            $winEvent->setRelation('user', $user);

            return [
                'rank'         => $index + 1,
                'winner_name'  => $winEvent->maskedWinnerName(),
                'total_amount' => (float) $row->total_amount,
                'wins_count'   => (int) $row->wins_count,
            ];
        });
    }
}
