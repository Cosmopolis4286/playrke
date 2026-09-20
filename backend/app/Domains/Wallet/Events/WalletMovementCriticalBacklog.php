<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando StaleWalletMovementAlertService::notifyCriticalPending()
 * detecta movimientos PENDING con más de CRITICAL_AGE_HOURS (24h) de
 * antigüedad — refleja en vivo el escalamiento crítico en la página de un
 * admin que ya la tiene abierta, sin depender solo de que abra el centro de
 * notificaciones in-app.
 *
 * Canal: admins.wallet-movements
 * Evento: .wallet.movement.critical-backlog
 */
class WalletMovementCriticalBacklog implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly int $criticalCount,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'wallet.movement.critical-backlog';
    }

    public function broadcastOn() : PrivateChannel
    {
        return new PrivateChannel('admins.wallet-movements');
    }

    public function broadcastWith() : array
    {
        return [
            'critical_count' => $this->criticalCount,
        ];
    }
}
