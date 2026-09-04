<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Persiste la notificación de wallet de forma asíncrona.
 * Se despacha desde broadcastWalletUpdated() para no bloquear
 * el hilo del caller (crítico en reembolsos y pagos masivos).
 */
class DispatchWalletNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $userId,
        private readonly float $balance,
        private readonly string $currencySymbol,
        private readonly string $movementType,
        private readonly float $amount,
        private readonly ?string $movementId = null,
        private readonly ?string $reason = null,
    ) {
    }

    public function handle(NotificationService $notificationService) : void
    {
        $notificationService->createWalletNotification(
            userId: $this->userId,
            balance: $this->balance,
            currencySymbol: $this->currencySymbol,
            movementType: $this->movementType,
            amount: $this->amount,
            movementId: $this->movementId,
            reason: $this->reason,
        );
    }
}
