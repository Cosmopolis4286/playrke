<?php

namespace App\DTOs\Roles\Subsidiary\Dashboard;

use App\DTOs\ContextDTO;

final class SubsidiaryDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales de la agencia */
        public readonly array $stats,
        /** Actividad reciente (ventas de sus vendedores) */
        public readonly array $recentActivity,
        /** Comercializadora a la que reporta esta agencia */
        public readonly ?array $reportsTo,
        /** Serie diaria de cartones vendidos (últimos 14 días) */
        public readonly array $salesTrend,
        /** Ventas agrupadas por vendedor (top 10, este mes) */
        public readonly array $salesBySeller,
        /** Billeteras propias de la agencia (saldo por moneda) */
        public readonly array $wallets,
        /** Movimientos de billetera propios pendientes de aprobación */
        public readonly int $pendingWalletCount,
        /** Período activo (e.g. "Marzo 2026") */
        public readonly string $period,
        /**
         * true solo si ESTA agencia tiene games.bingo.manage como permiso
         * individual — el rol 'subsidiary' no lo trae por defecto.
         */
        public readonly bool $canManageRooms = false,
        /** Salud de la red: vendieron hoy, sin vender en 7 días, no activos */
        public readonly array $networkHealth = ['total' => 0, 'active' => 0, 'sold_today' => 0, 'inactive' => 0, 'idle_count' => 0, 'idle' => []],
        /** Pagos de sus vendedores en revisión (los revisa la comercializadora) y rechazados */
        public readonly array $networkPayments = ['pending_count' => 0, 'pending_amount' => null, 'oldest_pending_at' => null, 'rejected_count' => 0],
        /** Premios de cartones de su red aún sin entregar */
        public readonly array $prizesToDeliver = ['count' => 0, 'amount' => null],
    ) {}

    public function toView(): array
    {
        return [
            'userName'           => $this->userName,
            'stats'              => $this->stats,
            'recentActivity'     => $this->recentActivity,
            'reportsTo'          => $this->reportsTo,
            'salesTrend'         => $this->salesTrend,
            'salesBySeller'      => $this->salesBySeller,
            'wallets'            => $this->wallets,
            'pendingWalletCount' => $this->pendingWalletCount,
            'period'             => $this->period,
            'canManageRooms'     => $this->canManageRooms,
            'networkHealth'      => $this->networkHealth,
            'networkPayments'    => $this->networkPayments,
            'prizesToDeliver'    => $this->prizesToDeliver,
        ];
    }
}
