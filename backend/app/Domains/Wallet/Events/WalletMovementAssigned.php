<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando un admin toma o libera un movimiento pendiente. Permite
 * que la tabla de otros admins refleje en vivo quién está revisando cada
 * caso, para que el lock real aplicado en
 * WalletService::guardNotLockedByOther() sea visible antes de que alguien
 * choque con el 409 al intentar aprobar/rechazar.
 *
 * Canal: admins.wallet-movements
 * Evento: .wallet.movement.assigned
 */
class WalletMovementAssigned implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $movementId,
        public readonly ?string $assignedToUserId,
        public readonly ?string $assignedToName,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'wallet.movement.assigned';
    }

    public function broadcastOn() : PrivateChannel
    {
        return new PrivateChannel('admins.wallet-movements');
    }

    public function broadcastWith() : array
    {
        return [
            'movement_id' => $this->movementId,
            'assigned_to_user_id' => $this->assignedToUserId,
            'assigned_to_name' => $this->assignedToName,
        ];
    }
}
