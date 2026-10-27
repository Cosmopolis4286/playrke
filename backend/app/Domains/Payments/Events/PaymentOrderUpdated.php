<?php

declare(strict_types=1);

namespace App\Domains\Payments\Events;

use App\Domains\Payments\Models\PaymentOrder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Emitido cada vez que una PaymentOrder cambia de status — un solo evento
 * genérico (no uno por transición) porque el frontend en /payments/{id}
 * solo necesita "status actual", nunca reacciona distinto según de dónde
 * vino la transición. Mismo canal por-usuario que WalletUpdated.
 */
class PaymentOrderUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly PaymentOrder $order,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'payment-order.updated';
    }

    public function broadcastOn() : PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->order->user_id}");
    }

    public function broadcastWith() : array
    {
        return [
            'id' => $this->order->id,
            'status' => $this->order->status,
            'expected_amount' => $this->order->expected_amount,
            'received_amount' => $this->order->received_amount,
            'expires_at' => $this->order->expires_at?->toIso8601String(),
        ];
    }
}
