<?php

namespace App\Services\Roles\Dashboard\Concerns;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use Carbon\Carbon;

/**
 * Serie diaria de cartones vendidos, compartida entre los dashboards de
 * Admin y SuperAdmin para el gráfico de tendencia.
 */
trait BuildsSalesTrend
{
    private function buildSalesTrend(int $days = 14): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $counts = BingoCard::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            $trend[] = [
                'date'  => $date,
                'count' => (int) ($counts[$date] ?? 0),
            ];
        }

        return $trend;
    }
}
