<?php

namespace App\DTOs\Roles\Seller\Dashboard;

use App\DTOs\ContextDTO;

final class SellerDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Comisión configurada (e.g. "5.00") */
        public readonly string $commission,
        /** Stats principales del vendedor */
        public readonly array $stats,
        /** Actividad reciente (ventas propias) */
        public readonly array $recentActivity,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
    ) {}

    public function toView(): array
    {
        return [
            'userName'       => $this->userName,
            'commission'     => $this->commission,
            'stats'          => $this->stats,
            'recentActivity' => $this->recentActivity,
            'period'         => $this->period,
        ];
    }
}
