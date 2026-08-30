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
        /** A quién reporta el vendedor: Commerce directo o Agencia/Subsidiary intermedia */
        public readonly ?array $reportsTo,
        /** Comisiones especiales configuradas por sala (sellers_room_limits) */
        public readonly array $roomCommissions,
        /** Serie diaria de cartones vendidos (últimos 14 días) */
        public readonly array $salesTrend,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
        /** Posición del vendedor en el ranking mensual — null si no vendió nada este mes */
        public readonly ?array $ranking = null,
    ) {}

    public function toView(): array
    {
        return [
            'userName'        => $this->userName,
            'commission'      => $this->commission,
            'stats'           => $this->stats,
            'recentActivity'  => $this->recentActivity,
            'reportsTo'       => $this->reportsTo,
            'roomCommissions' => $this->roomCommissions,
            'salesTrend'      => $this->salesTrend,
            'period'          => $this->period,
            'ranking'         => $this->ranking,
        ];
    }
}
