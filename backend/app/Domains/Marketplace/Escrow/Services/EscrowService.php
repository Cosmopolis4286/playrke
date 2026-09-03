<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Escrow\Services;

use App\Domains\Marketplace\Escrow\Events\EscrowStatusChanged;
use App\Domains\Marketplace\Escrow\Exceptions\InvalidEscrowStateException;
use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Domains\Marketplace\Inventory\Services\InventoryService;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Domains\Marketplace\Services\MarketplaceFeeService;
use App\Models\User;
use App\Models\WalletHold;
use App\Models\WalletMovement;
use App\Services\Platform\PlatformWalletService;
use App\Services\WalletReservationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta el ciclo de vida completo de un Escrow. Sigue el patrón de dos
 * fases de GamePrizeService::payout() (marcar estado bajo lock -> re-verificar
 * bajo lock antes de mover dinero/activo) — ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 17-19.
 *
 * Reutiliza sin modificar: WalletReservationService (hold/capture/release),
 * WalletService (recordMarketplaceCredit), PlatformWalletService
 * (creditMarketplaceFee), InventoryService (lock/unlock/transfer). Este
 * servicio NUNCA escribe wallets.balance ni player_assets.status
 * directamente — siempre delega.
 */
final class EscrowService
{
    /** Ventana por defecto antes de que un escrow no liberado expire. */
    private const DEFAULT_TTL_MINUTES = 30;

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly WalletReservationService $walletReservationService,
        private readonly WalletService $walletService,
        private readonly PlatformWalletService $platformWalletService,
        private readonly MarketplaceFeeService $feeService,
    ) {
    }

    /**
     * Crea el Escrow a partir de una Order ya en PENDING (Listing ya
     * RESERVED, ver OrderService::buy() — Fase 2). No mueve dinero ni
     * bloquea el activo todavía.
     */
    public function create(Order $order): Escrow
    {
        if ($order->status !== Order::STATUS_PENDING) {
            throw new InvalidEscrowStateException('La orden no está pendiente.');
        }

        /** @var Listing $listing */
        $listing = $order->listing;
        $amount  = $order->totalAmount();
        $feeAmount = $order->fee_amount;

        return DB::transaction(function () use ($order, $listing, $amount, $feeAmount) {
            $escrow = Escrow::create([
                'order_id'        => $order->id,
                'buyer_id'        => $order->buyer_id,
                'seller_id'       => $listing->seller_id,
                'player_asset_id' => $listing->player_asset_id,
                'quantity'        => $order->quantity,
                'amount'          => $amount,
                'fee_amount'      => $feeAmount,
                'seller_amount'   => round($amount - $feeAmount, 2),
                'currency_id'     => $listing->currency_id,
                'status'          => Escrow::STATUS_CREATED,
                'expires_at'      => now()->addMinutes(self::DEFAULT_TTL_MINUTES),
            ]);

            Order::where('id', $order->id)
                ->where('status', Order::STATUS_PENDING)
                ->update(['status' => Order::STATUS_ESCROW, 'escrow_id' => $escrow->id]);

            return $escrow;
        });
    }

    /**
     * Reserva los fondos del comprador vía WalletReservationService::hold().
     * CREATED -> FUNDED.
     */
    public function fund(Escrow $escrow): Escrow
    {
        $fresh = DB::transaction(function () use ($escrow) {
            $locked = $this->lockEscrow($escrow, Escrow::STATUS_CREATED);

            $buyerWallet = $this->walletService->findActiveWallet($locked->buyer_id, $locked->currency_id);
            if (!$buyerWallet) {
                throw new \DomainException('El comprador no tiene una billetera activa en la moneda de la compra.');
            }

            // hold() abre su propia transacción — Laravel la anida como
            // savepoint dentro de esta, así que si algo posterior falla
            // (el UPDATE de abajo) el hold también revierte.
            $hold = $this->walletReservationService->hold(
                wallet: $buyerWallet,
                amount: $locked->amount,
                description: "Escrow marketplace #{$locked->id}",
            );

            $affected = Escrow::where('id', $locked->id)
                ->where('status', Escrow::STATUS_CREATED)
                ->update(['status' => Escrow::STATUS_FUNDED, 'wallet_hold_id' => $hold->id]);

            if ($affected === 0) {
                throw new InvalidEscrowStateException('El escrow cambió de estado durante el funding.');
            }

            return $locked->fresh();
        });

        $this->broadcastStatus($fresh);

        return $fresh;
    }

    /**
     * Bloquea el activo del vendedor. FUNDED -> ASSET_LOCKED -> READY_TO_SETTLE.
     */
    public function lockAsset(Escrow $escrow): Escrow
    {
        $fresh = DB::transaction(function () use ($escrow) {
            $locked = $this->lockEscrow($escrow, Escrow::STATUS_FUNDED);

            $asset = $locked->playerAsset;
            $this->inventoryService->lock($asset, $locked->id, $locked->quantity);

            $affected = Escrow::where('id', $locked->id)
                ->where('status', Escrow::STATUS_FUNDED)
                ->update(['status' => Escrow::STATUS_READY_TO_SETTLE]);

            if ($affected === 0) {
                throw new InvalidEscrowStateException('El escrow cambió de estado durante el bloqueo del activo.');
            }

            return $locked->fresh();
        });

        $this->broadcastStatus($fresh);

        return $fresh;
    }

    /**
     * Settlement atómico e idempotente: transfiere el activo, captura la
     * reserva del comprador, acredita al vendedor y a la plataforma.
     * READY_TO_SETTLE -> RELEASED.
     *
     * Llamar release() dos veces sobre el mismo escrow es seguro: la
     * segunda llamada encuentra status != READY_TO_SETTLE (ya es RELEASED)
     * y retorna sin efecto — igual que GamePrizeService::payout(). Si dos
     * workers concurrentes pasan el guard de estado a la vez, los
     * idempotency_key de recordMarketplaceCredit()/creditMarketplaceFee()
     * evitan que la wallet del vendedor o de la plataforma se acredite dos
     * veces (ver WalletService::recordPrize() para el mecanismo exacto).
     */
    public function release(Escrow $escrow): Escrow
    {
        $fresh = DB::transaction(function () use ($escrow) {
            $locked = Escrow::where('id', $escrow->id)->lockForUpdate()->first();

            if (!$locked || $locked->status !== Escrow::STATUS_READY_TO_SETTLE) {
                return null;
            }

            // Transferir el activo primero: si esto falla, ninguna wallet se
            // tocó todavía (la transacción completa revierte).
            $this->inventoryService->transfer($locked->playerAsset, $locked->buyer_id, $locked->id);

            $hold = WalletHold::findOrFail($locked->wallet_hold_id);
            $this->walletReservationService->capture(
                hold: $hold,
                movementType: WalletMovement::TYPE_MARKETPLACE_PURCHASE,
                description: "Compra marketplace — escrow #{$locked->id}",
            );

            $sellerWallet = $this->walletService->findActiveWallet($locked->seller_id, $locked->currency_id);
            if (!$sellerWallet) {
                throw new \DomainException('El vendedor no tiene una billetera activa en la moneda de la venta.');
            }

            if ($locked->seller_amount > 0) {
                $this->walletService->recordMarketplaceCredit(
                    wallet: $sellerWallet,
                    amount: $locked->seller_amount,
                    description: "Venta marketplace — escrow #{$locked->id}",
                    idempotencyKey: "marketplace_escrow:{$locked->id}:seller_credit",
                );
            }

            if ($locked->fee_amount > 0) {
                $this->platformWalletService->creditMarketplaceFee(
                    currencyId: $locked->currency_id,
                    amount: $locked->fee_amount,
                    idempotencyKey: "marketplace_escrow:{$locked->id}:platform_fee",
                    description: "Comisión marketplace — escrow #{$locked->id}",
                );
            }

            Escrow::where('id', $locked->id)->update(['status' => Escrow::STATUS_RELEASED]);
            Order::where('id', $locked->order_id)->update(['status' => Order::STATUS_COMPLETED]);
            Listing::where('id', $locked->order->listing_id)->update(['status' => Listing::STATUS_SOLD]);

            Log::info('[EscrowService] Escrow liberado — settlement completo', [
                'escrow_id'      => $locked->id,
                'order_id'       => $locked->order_id,
                'buyer_id'       => $locked->buyer_id,
                'seller_id'      => $locked->seller_id,
                'amount'         => $locked->amount,
                'fee_amount'     => $locked->fee_amount,
                'seller_amount'  => $locked->seller_amount,
            ]);

            return $locked->fresh();
        });

        if ($fresh === null) {
            Log::info('[EscrowService] release() ignorado — escrow no está en READY_TO_SETTLE (ya liberado o en otro estado)', [
                'escrow_id' => $escrow->id,
            ]);
            return $escrow->fresh();
        }

        $this->broadcastStatus($fresh);

        return $fresh;
    }

    /**
     * Cancela un escrow antes de fondear (CREATED -> CANCELLED). No hay
     * fondos ni activo que revertir en este estado.
     */
    public function cancel(Escrow $escrow): Escrow
    {
        return DB::transaction(function () use ($escrow) {
            $affected = Escrow::where('id', $escrow->id)
                ->where('status', Escrow::STATUS_CREATED)
                ->update(['status' => Escrow::STATUS_CANCELLED]);

            if ($affected === 0) {
                throw new InvalidEscrowStateException('El escrow ya no puede cancelarse (usa refund() si ya está fondeado).');
            }

            Order::where('id', $escrow->order_id)->update(['status' => Order::STATUS_CANCELLED]);
            Listing::where('id', $escrow->order->listing_id)
                ->where('status', Listing::STATUS_RESERVED)
                ->update(['status' => Listing::STATUS_ACTIVE]);

            $fresh = $escrow->fresh();
            $this->broadcastStatus($fresh);

            return $fresh;
        });
    }

    /**
     * Revierte un escrow ya fondeado (FUNDED o ASSET_LOCKED/READY_TO_SETTLE,
     * pero NO ya liberado). Libera el hold sin capturar y desbloquea el
     * activo si llegó a bloquearse.
     */
    public function refund(Escrow $escrow): Escrow
    {
        return DB::transaction(function () use ($escrow) {
            $locked = Escrow::where('id', $escrow->id)->lockForUpdate()->first();

            if (!$locked || !in_array($locked->status, [
                Escrow::STATUS_FUNDED, Escrow::STATUS_ASSET_LOCKED, Escrow::STATUS_READY_TO_SETTLE, Escrow::STATUS_DISPUTED,
            ], true)) {
                throw new InvalidEscrowStateException('El escrow no puede reembolsarse desde su estado actual.');
            }

            if ($locked->wallet_hold_id) {
                $hold = WalletHold::find($locked->wallet_hold_id);
                if ($hold && $hold->isActive()) {
                    $this->walletReservationService->release($hold);
                }
            }

            if (in_array($locked->status, [Escrow::STATUS_ASSET_LOCKED, Escrow::STATUS_READY_TO_SETTLE, Escrow::STATUS_DISPUTED], true)) {
                $asset = $locked->playerAsset;
                if ($asset->isLocked()) {
                    $this->inventoryService->unlock($asset);
                }
            }

            Escrow::where('id', $locked->id)->update(['status' => Escrow::STATUS_REFUNDED]);
            Order::where('id', $locked->order_id)->update(['status' => Order::STATUS_CANCELLED]);
            Listing::where('id', $locked->order->listing_id)
                ->where('status', Listing::STATUS_RESERVED)
                ->update(['status' => Listing::STATUS_ACTIVE]);

            $fresh = $locked->fresh();
            $this->broadcastStatus($fresh);

            return $fresh;
        });
    }

    /**
     * Expira un escrow activo cuyo expires_at ya venció, revirtiendo todo
     * vía refund(). Pensado para un scheduled command (fuera de alcance de
     * esta fase).
     */
    public function expire(Escrow $escrow): Escrow
    {
        if (!$escrow->isActive()) {
            throw new InvalidEscrowStateException('El escrow ya está en un estado final.');
        }

        if ($escrow->status === Escrow::STATUS_CREATED) {
            return $this->cancel($escrow);
        }

        $refunded = $this->refund($escrow);

        Escrow::where('id', $refunded->id)->update(['status' => Escrow::STATUS_EXPIRED]);

        return $refunded->fresh();
    }

    /**
     * Abre una disputa sobre un escrow READY_TO_SETTLE. Congela el escrow
     * (ni release() ni refund() lo tocan mientras esté DISPUTED — solo
     * resolve()).
     */
    public function dispute(Escrow $escrow, string $reason): Escrow
    {
        $affected = Escrow::where('id', $escrow->id)
            ->where('status', Escrow::STATUS_READY_TO_SETTLE)
            ->update(['status' => Escrow::STATUS_DISPUTED, 'dispute_reason' => $reason]);

        if ($affected === 0) {
            throw new InvalidEscrowStateException('Solo se puede disputar un escrow listo para liquidar.');
        }

        $fresh = $escrow->fresh();
        $this->broadcastStatus($fresh);

        return $fresh;
    }

    /**
     * Resuelve una disputa. BUYER_WINS revierte todo (refund); SELLER_WINS
     * completa la venta (release); CANCELLED es equivalente a BUYER_WINS
     * pero con motivo distinto en la auditoría. PARTIAL queda fuera de esta
     * fase (requiere montos parciales explícitos del admin — no hay caso de
     * uso real todavía para calibrar la interfaz).
     */
    public function resolve(Escrow $escrow, string $resolution, User $admin): Escrow
    {
        if ($escrow->status !== Escrow::STATUS_DISPUTED) {
            throw new InvalidEscrowStateException('Solo se puede resolver un escrow en disputa.');
        }

        if (!in_array($resolution, [Escrow::RESOLUTION_BUYER_WINS, Escrow::RESOLUTION_SELLER_WINS, Escrow::RESOLUTION_CANCELLED], true)) {
            throw new \InvalidArgumentException('Resolución no soportada en esta fase: ' . $resolution);
        }

        // Volver a READY_TO_SETTLE es necesario porque release()/refund()
        // exigen ese estado de entrada explícito — resolve() es la única vía
        // autorizada para hacer esa transición de vuelta tras una disputa.
        Escrow::where('id', $escrow->id)
            ->where('status', Escrow::STATUS_DISPUTED)
            ->update(['status' => Escrow::STATUS_READY_TO_SETTLE]);

        $resolved = $resolution === Escrow::RESOLUTION_SELLER_WINS
            ? $this->release($escrow->fresh())
            : $this->refund($escrow->fresh());

        Escrow::where('id', $resolved->id)->update([
            'resolution'   => $resolution,
            'resolved_by'  => $admin->id,
            'resolved_at'  => now(),
        ]);

        return $resolved->fresh();
    }

    /**
     * SELECT ... FOR UPDATE + verificación de estado de entrada. Todas las
     * transiciones de una sola fase (fund/lockAsset) pasan por aquí.
     */
    private function lockEscrow(Escrow $escrow, string $expectedStatus): Escrow
    {
        $locked = Escrow::where('id', $escrow->id)->lockForUpdate()->first();

        if (!$locked || $locked->status !== $expectedStatus) {
            throw new InvalidEscrowStateException("El escrow no está en estado {$expectedStatus}.");
        }

        return $locked;
    }

    private function broadcastStatus(Escrow $escrow): void
    {
        try {
            broadcast(new EscrowStatusChanged(
                escrowId: $escrow->id,
                buyerId: $escrow->buyer_id,
                sellerId: $escrow->seller_id,
                status: $escrow->status,
            ));
        } catch (\Throwable $e) {
            Log::warning('[EscrowService] broadcastStatus falló (WebSocket no disponible): ' . $e->getMessage());
        }
    }
}
