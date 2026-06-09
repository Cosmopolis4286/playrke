<?php

namespace App\Services\Roles\SuperAdmin\Rke;

use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use Illuminate\Support\Facades\DB;

final class RkeSummaryService
{
    /**
     * Resumen de comisiones RKE agrupadas por sala.
     *
     * Devuelve:
     *  - `totals`: agregados históricos globales
     *  - `rooms`: lista por sala con anfitrión, total vendido, RKE cobrado y fecha
     */
    public function getSummary(): array
    {
        $rows = BingoCreatorSale::query()
            ->select([
                'room_id',
                DB::raw('SUM(sale_amount)    AS total_sales'),
                DB::raw('SUM(rke_commission) AS total_rke'),
                DB::raw('MIN(created_at)     AS first_sale_at'),
                DB::raw('MAX(created_at)     AS last_sale_at'),
            ])
            ->groupBy('room_id')
            ->orderByDesc(DB::raw('SUM(rke_commission)'))
            ->with(['room' => fn($q) => $q->with(['config', 'creator:id,first_name,last_name,username'])])
            ->get();

        $totalRke   = (float) BingoCreatorSale::sum('rke_commission');
        $totalSales = (float) BingoCreatorSale::sum('sale_amount');

        $rooms = $rows->map(function ($row) {
            $room    = $row->room;
            $creator = $room?->creator;

            if ($creator) {
                $fullName    = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''));
                $creatorName = $fullName !== '' ? $fullName : ($creator->username ?? 'Desconocido');
            } else {
                $creatorName = 'Desconocido';
            }

            return [
                'room_id'       => (string) $row->room_id,
                'room_name'     => $room?->uuid_code ?? ('Sala #' . substr((string) $row->room_id, -6)),
                'creator_name'  => $creatorName,
                'total_sales'   => round((float) $row->total_sales, 2),
                'total_rke'     => round((float) $row->total_rke, 2),
                'rke_pct'       => $row->total_sales > 0
                    ? round(((float) $row->total_rke / (float) $row->total_sales) * 100, 2)
                    : 0.0,
                'first_sale_at' => $row->first_sale_at,
                'last_sale_at'  => $row->last_sale_at,
                'status'        => $room?->status?->code ?? null,
            ];
        })->values();

        return [
            'totals' => [
                'total_rke'   => round($totalRke, 2),
                'total_sales' => round($totalSales, 2),
                'room_count'  => $rows->count(),
            ],
            'rooms' => $rooms,
        ];
    }
}
