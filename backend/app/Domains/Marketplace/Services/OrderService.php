<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Exceptions\InvalidListingException;
use App\Domains\Marketplace\Exceptions\ListingNoLongerAvailableException;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * OrderService::buy() es el único punto de entrada para comprar un listing.
 * Ver MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 8 y 18: la reserva del
 * listing es un UPDATE ... WHERE status='ACTIVE' — 0 filas afectadas
 * significa que otro comprador ganó la carrera (o el vendedor canceló) en
 * el instante entre leer el listing y comprarlo, nunca una excepción a
 * ignorar.
 *
 * Esta fase (2) NO mueve dinero ni bloquea el PlayerAsset — solo reserva el
 * listing y crea la Order en PENDING. EscrowService (Fase 3) es quien
 * continúa el flujo (fund -> lockAsset -> release) tomando esta Order.
 */
final class OrderService
{
    public function __construct(
        private readonly MarketplaceFeeService $feeService,
    ) {
    }

    public function buy(Listing $listing, string $buyerId, int $quantity): Order
    {
        if ((string) $listing->seller_id === (string) $buyerId) {
            throw new InvalidListingException('No puedes comprar tu propio listing.');
        }

        if ($quantity < 1 || $quantity > $listing->quantity) {
            throw new InvalidListingException('La cantidad solicitada excede la publicada.');
        }

        return DB::transaction(function () use ($listing, $buyerId, $quantity) {
            $affected = Listing::where('id', $listing->id)
                ->where('status', Listing::STATUS_ACTIVE)
                ->update(['status' => Listing::STATUS_RESERVED]);

            if ($affected === 0) {
                throw new ListingNoLongerAvailableException('Este listing ya no está disponible.');
            }

            $fresh = $listing->fresh();
            $totalAmount = round($fresh->price * $quantity, 2);
            $feeAmount   = $this->feeService->calculate($fresh, $totalAmount);

            return Order::create([
                'listing_id'  => $fresh->id,
                'buyer_id'    => $buyerId,
                'quantity'    => $quantity,
                'price'       => $fresh->price,
                'fee_amount'  => $feeAmount,
                'status'      => Order::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Cancela una Order PENDING (antes de que exista escrow, Fase 3) y
     * revierte el listing a ACTIVE. Una vez que la Order avanza a ESCROW,
     * la cancelación pasa por EscrowService::cancel()/refund(), no por aquí.
     */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $affected = Order::where('id', $order->id)
                ->where('status', Order::STATUS_PENDING)
                ->update(['status' => Order::STATUS_CANCELLED]);

            if ($affected === 0) {
                throw new \DomainException('La orden ya no está pendiente.');
            }

            Listing::where('id', $order->listing_id)
                ->where('status', Listing::STATUS_RESERVED)
                ->update(['status' => Listing::STATUS_ACTIVE]);

            return $order->fresh();
        });
    }
}
