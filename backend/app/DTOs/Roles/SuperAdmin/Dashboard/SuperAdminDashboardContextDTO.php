<?php

namespace App\DTOs\Roles\SuperAdmin\Dashboard;

use App\DTOs\ContextDTO;

final class SuperAdminDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales del sistema */
        public readonly array $stats,
        /** Actividad reciente */
        public readonly array $recentActivity,
        /** Actividad de administradores */
        public readonly array $adminActivity,
        /** Totales de entidades por rol */
        public readonly array $entityCounts,
        /** Salud del sistema */
        public readonly array $systemHealth,
        /** Serie diaria de cartones vendidos (últimos 14 días) */
        public readonly array $salesTrend,
        /** Movimientos de billetera pendientes de aprobación */
        public readonly int $pendingWalletCount,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
        /** Labels de accesos rápidos visibles para este usuario (permiso + visibilidad configurable) */
        public readonly array $allowedQuickLinks,
    ) {}

    public function toView(): array
    {
        return [
            'userName'           => $this->userName,
            'stats'              => $this->stats,
            'recentActivity'     => $this->recentActivity,
            'adminActivity'      => $this->adminActivity,
            'entityCounts'       => $this->entityCounts,
            'systemHealth'       => $this->systemHealth,
            'salesTrend'         => $this->salesTrend,
            'pendingWalletCount' => $this->pendingWalletCount,
            'period'             => $this->period,
            'allowedQuickLinks'  => $this->allowedQuickLinks,
        ];
    }
}
