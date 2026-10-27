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
        /** Ventas con pago en revisión y rechazadas recientes (hay que devolver el dinero) */
        public readonly array $paymentReview = ['pending_count' => 0, 'pending_amount' => null, 'pending_cards' => 0, 'oldest_pending_at' => null, 'rejected' => []],
        /** Salas cuya venta cierra en las próximas 24 h */
        public readonly array $closingRooms = [],
        /** Códigos de compra otorgados a compradores recurrentes */
        public readonly array $purchaseCodes = ['active' => 0, 'remaining_uses' => 0, 'expiring_soon' => 0],
        /** Premios de sus compradores aún sin entregar */
        public readonly array $prizesToDeliver = ['count' => 0, 'amount' => null],
    ) {
    }

    public function toView() : array
    {
        return [
            'userName' => $this->userName,
            'commission' => $this->commission,
            'stats' => $this->stats,
            'recentActivity' => $this->recentActivity,
            'reportsTo' => $this->reportsTo,
            'roomCommissions' => $this->roomCommissions,
            'salesTrend' => $this->salesTrend,
            'period' => $this->period,
            'ranking' => $this->ranking,
            'paymentReview' => $this->paymentReview,
            'closingRooms' => $this->closingRooms,
            'purchaseCodes' => $this->purchaseCodes,
            'prizesToDeliver' => $this->prizesToDeliver,
        ];
    }
}
