<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Persiste la notificación de wallet de forma asíncrona.
 * Se despacha desde broadcastWalletUpdated() para no bloquear
 * el hilo del caller (crítico en reembolsos y pagos masivos).
 *
 * ShouldBeUnique: createWalletNotification() siempre INSERTA una fila
 * nueva, nunca actualiza una existente — sin esto, un reintento (tries=3,
 * ej. tras un timeout transitorio de BD que no es culpa del propio
 * create()) duplicaría la notificación in-app del mismo movimiento.
 * uniqueId() usa movementId cuando existe (identifica un movimiento real
 * de forma inequívoca); cuando es null (algún caller legacy que no lo
 * pasa) cae a un hash de los demás campos, mejor que no deduplicar nada.
 */
class DispatchWalletNotificationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Ventana en la que Laravel considera "en curso" un job con la misma
     * uniqueId() — cubre el tiempo entre los 3 tries con su backoff, no
     * necesita ser mayor.
     */
    public int $uniqueFor = 300;

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

    public function uniqueId() : string
    {
        return $this->movementId
            ?? md5($this->userId . '|' . $this->movementType . '|' . $this->amount . '|' . ($this->reason ?? ''));
    }
}
