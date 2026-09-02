<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Escrow\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Emitido cuando un Escrow cambia a un estado relevante para lo que el
 * comprador/vendedor ve en pantalla (no cada transición interna) — mismo
 * criterio que WalletUpdated. Se difunde a ambos canales privados, no a uno
 * compartido, para que cada lado reciba el evento sin exponer datos del otro.
 */
class EscrowStatusChanged implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $escrowId,
        public readonly string $buyerId,
        public readonly string $sellerId,
        public readonly string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->buyerId}"),
            new PrivateChannel("App.Models.User.{$this->sellerId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'marketplace.escrow.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'escrow_id' => $this->escrowId,
            'status'    => $this->status,
        ];
    }
}
