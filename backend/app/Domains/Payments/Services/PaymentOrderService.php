<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Exceptions\PaymentProductNotAvailableException;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crea y consulta PaymentOrder. Ver PAYMENTS_USDT_TRC20_DESIGN.md secciones
 * 4 y 6.
 *
 * Cada orden deriva su PROPIA dirección de depósito (derivation_index
 * secuencial global sobre payment_orders, nunca reutilizado — unique en la
 * migración) para poder identificar el pago solo por dirección de destino,
 * incluso con varias órdenes concurrentes del mismo usuario. El índice de
 * derivación nunca se calcula dos veces para la misma orden: se reserva
 * dentro de una transacción con reintento ante colisión de índice único,
 * mismo patrón que WalletService::recordPrize().
 */
final class PaymentOrderService
{
    public function __construct(
        private readonly BlockchainProviderInterface $blockchain,
    ) {
    }

    public function create(string $userId, string $paymentProductCode) : PaymentOrder
    {
        $product = PaymentProduct::where('code', $paymentProductCode)->active()->first();

        if (! $product) {
            throw new PaymentProductNotAvailableException(
                "El producto '{$paymentProductCode}' no existe o no está disponible.",
            );
        }

        return $this->createWithRetry($userId, $product);
    }

    public function findForUser(string $orderId, string $userId) : ?PaymentOrder
    {
        return PaymentOrder::where('id', $orderId)->where('user_id', $userId)->first();
    }

    private function createWithRetry(string $userId, PaymentProduct $product, int $attempt = 0) : PaymentOrder
    {
        try {
            return DB::transaction(function() use ($userId, $product) {
                $derivationIndex = ((int) PaymentOrder::max('derivation_index')) + 1;
                $depositAddress = $this->blockchain->deriveAddress($derivationIndex);

                return PaymentOrder::create([
                    'user_id' => $userId,
                    'payment_product_id' => $product->id,
                    'network' => $this->blockchain->network(),
                    'token' => 'USDT',
                    'token_contract' => $this->blockchain->tokenContract(),
                    'derivation_index' => $derivationIndex,
                    'deposit_address' => $depositAddress,
                    'expected_amount' => $product->price_usdt,
                    'status' => PaymentOrder::STATUS_PENDING,
                    'expires_at' => now()->addMinutes((int) config('payments.order_expiration_minutes', 30)),
                ]);
            });
        } catch (QueryException $e) {
            // Colisión de derivation_index bajo dos creaciones concurrentes
            // (ver mismo patrón en WalletService::recordPrize()) — reintenta
            // con un índice nuevo, un número acotado de veces.
            if (str_contains($e->getMessage(), 'derivation_index') && $attempt < 3) {
                return $this->createWithRetry($userId, $product, $attempt + 1);
            }

            throw $e;
        }
    }
}
