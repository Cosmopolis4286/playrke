<?php

namespace App\Services\Roles\Dashboard\Concerns;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Serie diaria de cartones vendidos, compartida entre los dashboards de
 * Admin, SuperAdmin, Commerce y Seller para el gráfico de tendencia.
 */
trait BuildsSalesTrend
{
    /**
     * @param int $days Tamaño del período (días hacia atrás desde hoy).
     * @param (callable(Builder): void)|null $scope Filtro adicional sobre la
     *   query base (ej. limitar a un creator_user_id/seller_user_id). Sin
     *   scope, cuenta cartones de todo el sistema (uso de Admin/SuperAdmin).
     */
    private function buildSalesTrend(int $days = 14, ?callable $scope = null): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $query = BingoCard::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate);

        if ($scope !== null) {
            $scope($query);
        }

        $counts = $query->groupBy('date')->pluck('count', 'date');

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
