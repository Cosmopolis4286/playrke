<?php

namespace App\DTOs\Roles\Admin\Dashboard;

use App\DTOs\ContextDTO;

final class AdminDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales del sistema */
        public readonly array $stats,
        /** Actividad reciente */
        public readonly array $recentActivity,
        /** Totales de entidades por rol */
        public readonly array $entityCounts,
        /** Serie diaria de cartones vendidos (últimos 14 días) */
        public readonly array $salesTrend,
        /** Totales de comisiones RKE cobradas (resumen, sin desglose por sala — ver SuperAdmin para el detalle completo) */
        public readonly array $rkeTotals,
        /** Movimientos de billetera pendientes de aprobación */
        public readonly int $pendingWalletCount,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
    ) {}

    public function toView(): array
    {
        return [
            'userName'           => $this->userName,
            'stats'              => $this->stats,
            'recentActivity'     => $this->recentActivity,
            'entityCounts'       => $this->entityCounts,
            'salesTrend'         => $this->salesTrend,
            'rkeTotals'          => $this->rkeTotals,
            'pendingWalletCount' => $this->pendingWalletCount,
            'period'             => $this->period,
        ];
    }
}
