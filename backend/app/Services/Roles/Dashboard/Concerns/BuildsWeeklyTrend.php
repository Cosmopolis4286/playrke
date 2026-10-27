<?php

namespace App\Services\Roles\Dashboard\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Calcula la variación semana-a-semana de un conteo (registros de esta
 * semana vs. la semana anterior), para el indicador de tendencia de las
 * StatCards del dashboard.
 */
trait BuildsWeeklyTrend
{
    /**
     * @param Builder $query Query ya filtrada (scope, tipo de entidad, etc.),
     *                       sin condición de fecha — esta se aplica aquí.
     *
     * @return array{trend: 'up'|'down'|'neutral', trendValue: int, trendLabel: string}
     */
    private function buildWeeklyTrend(Builder $query) : array
    {
        $now = now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();

        $thisWeek = (clone $query)->where('created_at', '>=', $startOfWeek)->count();
        $lastWeek = (clone $query)->whereBetween('created_at', [$startOfLastWeek, $startOfWeek])->count();

        if ($lastWeek === 0) {
            $trend = $thisWeek > 0 ? 'up' : 'neutral';
            $trendValue = $thisWeek > 0 ? 100 : 0;
        } else {
            $diff = $thisWeek - $lastWeek;
            $trend = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral');
            $trendValue = (int) round(abs($diff) / $lastWeek * 100);
        }

        return [
            'trend' => $trend,
            'trendValue' => $trendValue,
            'trendLabel' => 'vs. semana anterior',
        ];
    }
}
