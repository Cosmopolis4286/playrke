<?php

namespace App\Services\Platform;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use Illuminate\Support\Facades\Cache;

/**
 * Métricas agregadas de la plataforma para prueba social pública (landing,
 * trust bar) — no un servicio de analítica genérico, solo los tres números
 * que hoy necesita esa vista. Si se necesitan más métricas más adelante,
 * se extiende aquí.
 */
final class PlatformStatsService
{
    private const CACHE_TTL_SECONDS = 60;

    public function summary(): array
    {
        return Cache::remember('platform:stats:summary', self::CACHE_TTL_SECONDS, function () {
            return [
                'players_online'   => $this->playersOnline(),
                'total_paid_usd'   => $this->totalPaidUsd(),
                'rooms_played_count' => $this->roomsPlayedCount(),
            ];
        });
    }

    private function playersOnline(): int
    {
        return (int) BingoRoom::query()
            ->whereHas('status', fn ($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
            ->sum('total_players');
    }

    private function totalPaidUsd(): float
    {
        return (float) WinEvent::query()
            ->where('prize_kind', WinEvent::PRIZE_MONEY)
            ->sum('amount_usd');
    }

    private function roomsPlayedCount(): int
    {
        return BingoRoom::query()
            ->whereHas('status', fn ($q) => $q->where('code', 'FINISHED'))
            ->count();
    }
}
