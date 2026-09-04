<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando un admin aprueba o rechaza un movimiento pendiente.
 * Permite que el navbar de otros admins actualice el contador de pendientes
 * sin recargar la página.
 *
 * Canal: admins.wallet-movements
 * Evento: .wallet.movement.processed
 */
class WalletMovementProcessed implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $movementId,
        public readonly string $movementType,
        public readonly string $status,         // COMPLETED | REJECTED
        public readonly int $pendingCount,   // nuevo total de pendientes tras el cambio
    ) {
    }

    public function broadcastAs() : string
    {
        return 'wallet.movement.processed';
    }

    public function broadcastOn() : PrivateChannel
    {
        return new PrivateChannel('admins.wallet-movements');
    }

    public function broadcastWith() : array
    {
        return [
            'movement_id' => $this->movementId,
            'movement_type' => $this->movementType,
            'status' => $this->status,
            'pending_count' => $this->pendingCount,
        ];
    }
}
