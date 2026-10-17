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
        /** Ventas agrupadas por vendedor (top 10, este mes) */
        public readonly array $salesBySeller,
        /** Billeteras propias del comercio (saldo por moneda) */
        public readonly array $wallets,
        /** Monedas y juegos habilitados para el comercio */
        public readonly array $configuration,
        /** Movimientos de billetera propios pendientes de aprobación */
        public readonly int $pendingWalletCount,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
        /** RKE que el comercio le debe a la plataforma por ventas fuera del sistema, por moneda */
        public readonly array $rkeReceivables = [],
        /** Salud de la red: vendieron hoy, sin vender en 7 días, no activos */
        public readonly array $networkHealth = ['total' => 0, 'active' => 0, 'sold_today' => 0, 'inactive' => 0, 'idle_count' => 0, 'idle' => []],
        /** Pagos de la red por revisar (los revisa el comercio) y rechazados */
        public readonly array $networkPayments = ['pending_count' => 0, 'pending_amount' => null, 'oldest_pending_at' => null, 'rejected_count' => 0],
        /** Premios de cartones de su red aún sin entregar */
        public readonly array $prizesToDeliver = ['count' => 0, 'amount' => null],
        /** Próximas salas propias (hoy y mañana) con su ocupación */
        public readonly array $upcomingRooms = [],
    ) {}

    public function toView(): array
    {
        return [
            'userName'           => $this->userName,
            'stats'              => $this->stats,
            'recentActivity'     => $this->recentActivity,
            'entityCounts'       => $this->entityCounts,
            'salesTrend'         => $this->salesTrend,
            'salesBySeller'      => $this->salesBySeller,
            'wallets'            => $this->wallets,
            'configuration'      => $this->configuration,
            'pendingWalletCount' => $this->pendingWalletCount,
            'period'             => $this->period,
            'rkeReceivables'     => $this->rkeReceivables,
            'networkHealth'      => $this->networkHealth,
            'networkPayments'    => $this->networkPayments,
            'prizesToDeliver'    => $this->prizesToDeliver,
            'upcomingRooms'      => $this->upcomingRooms,
        ];
    }
}
