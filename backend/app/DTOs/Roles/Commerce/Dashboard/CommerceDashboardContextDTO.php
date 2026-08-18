<?php

namespace App\DTOs\Roles\Commerce\Dashboard;

use App\DTOs\ContextDTO;

final class CommerceDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales del comercio */
        public readonly array $stats,
        /** Actividad reciente (ventas de la red de vendedores) */
        public readonly array $recentActivity,
        /** Totales de la red de vendedores propia (vendedores, agencias) */
        public readonly array $entityCounts,
        /** Serie diaria de cartones vendidos (últimos 14 días) */
        public readonly array $salesTrend,
        /** Movimientos de billetera propios pendientes de aprobación */
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
            'pendingWalletCount' => $this->pendingWalletCount,
            'period'             => $this->period,
        ];
    }
}
