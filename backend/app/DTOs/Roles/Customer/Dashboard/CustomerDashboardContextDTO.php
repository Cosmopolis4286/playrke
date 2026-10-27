<?php

namespace App\DTOs\Roles\Customer\Dashboard;

use App\DTOs\ContextDTO;

final class CustomerDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales de sus salas */
        public readonly array $stats,
        /** Próximas salas propias (hoy y mañana) */
        public readonly array $upcomingRooms,
        /** Serie diaria de cartones vendidos en sus salas (últimos 14 días) */
        public readonly array $salesTrend,
        /** Últimas ventas en sus salas */
        public readonly array $recentActivity,
        /** Billeteras propias (saldo por moneda) */
        public readonly array $wallets,
        /** Período activo (e.g. "Octubre 2026") */
        public readonly string $period,
    ) {
    }

    public function toView() : array
    {
        return [
            'userName' => $this->userName,
            'stats' => $this->stats,
            'upcomingRooms' => $this->upcomingRooms,
            'salesTrend' => $this->salesTrend,
            'recentActivity' => $this->recentActivity,
            'wallets' => $this->wallets,
            'period' => $this->period,
        ];
    }
}
