<?php

namespace App\Events\Wallet;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando un usuario solicita un depósito o retiro.
 *
 * Canal: admins.wallet-movements  (público para todos los admins/superadmins)
 * Evento: .wallet.movement.requested
 */
class WalletMovementRequested implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $movementId,
        public readonly string $userId,
        public readonly string $userName,
        public readonly string $movementType,   // DEPOSIT | WITHDRAWAL
        public readonly float  $amount,
        public readonly string $currencySymbol,
        public readonly string $currencyIso,
    ) {}

    public function broadcastOn(): Channel
    {
        // Canal privado compartido por todos los admins
        return new \Illuminate\Broadcasting\PrivateChannel('admins.wallet-movements');
    }

    public function broadcastAs(): string
    {
        return 'wallet.movement.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'movement_id'     => $this->movementId,
            'user_id'         => $this->userId,
            'user_name'       => $this->userName,
            'movement_type'   => $this->movementType,
            'amount'          => $this->amount,
            'currency_symbol' => $this->currencySymbol,
            'currency_iso'    => $this->currencyIso,
            'url'             => '/wallet-movements',
        ];
    }
}
