<?php

namespace App\Domains\Wallet\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Emitido cada vez que el saldo de una billetera cambia.
 * Escucha en el frontend: canal privado App.Models.User.{userId}
 */
class WalletUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string  $userId,
        public readonly string  $walletId,
        public readonly float   $balance,
        public readonly string  $currencySymbol,
        public readonly string  $movementType,
        public readonly float   $amount,
        public readonly ?string $movementStatus = null,
        public readonly ?string $movementId     = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'wallet.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'wallet_id'       => $this->walletId,
            'balance'         => $this->balance,
            'currency_symbol' => $this->currencySymbol,
            'movement_type'   => $this->movementType,
            'amount'          => $this->amount,
            'movement_status' => $this->movementStatus,
            'movement_id'     => $this->movementId,
        ];
    }
}
