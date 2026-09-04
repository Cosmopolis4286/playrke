<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Marketplace\Inventory\Services\AssetConsumptionService;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\Purchase;
use Illuminate\Support\Facades\DB;

/**
 * Entrega el producto de una PaymentOrder ya CONFIRMED — nunca se llama
 * desde el controller de un webhook, solo desde BlockchainWatcherService
 * después de validar on-chain. Ver PAYMENTS_USDT_TRC20_DESIGN.md secciones
 * 1 y 4.
 *
 * Idempotente por partida doble: purchases.payment_order_id es UNIQUE a
 * nivel de esquema (defensa estructural) Y el guard
 * UPDATE...WHERE status = CONFIRMED evita que dos llamadas concurrentes
 * entreguen el producto dos veces. Nunca reemplaza a
 * AssetConsumptionService::grantByCode() (que ya hace su propio
 * lockForUpdate) — es la fachada que ata "este pago" a "este otorgamiento".
 */
final class PurchaseService
{
    public function __construct(
        private readonly AssetConsumptionService $assetConsumption,
    ) {
    }

    public function deliver(PaymentOrder $order): Purchase
    {
        $existing = Purchase::where('payment_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($order) {
            $affected = PaymentOrder::where('id', $order->id)
                ->where('status', PaymentOrder::STATUS_CONFIRMED)
                ->update(['status' => PaymentOrder::STATUS_CREDITED]);

            if ($affected === 0) {
                // O ya se entregó (otra llamada ganó la carrera) o la orden
                // no está en un estado válido para entregar — en el primer
                // caso, purchases.payment_order_id ya existe y se devuelve
                // abajo; en el segundo, no hay nada que entregar.
                $existing = Purchase::where('payment_order_id', $order->id)->first();
                if ($existing) {
                    return $existing;
                }

                throw new \App\Domains\Payments\Exceptions\InvalidPaymentOrderStateException(
                    "La orden {$order->id} no está en estado CONFIRMED."
                );
            }

            $product = $order->paymentProduct;

            $playerAsset = $this->assetConsumption->grantByCode(
                userId: $order->user_id,
                assetDefinitionCode: $product->assetDefinition->code,
                quantity: $product->asset_quantity,
                acquiredReference: "payment_order:{$order->id}",
                acquiredVia: 'USDT_PURCHASE',
            );

            return Purchase::create([
                'user_id' => $order->user_id,
                'payment_order_id' => $order->id,
                'payment_product_id' => $product->id,
                'player_asset_id' => $playerAsset->id,
                'delivered_at' => now(),
            ]);
        });
    }
}
