<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reembolsa la entrada pagada por cada participante confirmado de una sala
 * de Trivia. Los reembolsos son idempotentes para permitir reintentos seguros.
 */
class TriviaRoomRefundService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @return int Cantidad de reembolsos nuevos aplicados.
     */
    public function refundConfirmedParticipants(TriviaRoom $room, string $reasonMessage): int
    {
        $participants = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', (string) $room->id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['confirmed', 'registered'])
            ->where('paid_amount', '>', 0)
            ->get();

        $refundedCount = 0;

        foreach ($participants as $participant) {
            DB::transaction(function () use ($room, $participant, $reasonMessage, &$refundedCount) {
                $wallet = $this->walletService->findActiveWallet(
                    (string) $participant->user_id,
                    (string) $participant->paid_currency_id,
                );

                if (! $wallet) {
                    Log::warning('[TriviaRoomRefundService] Wallet no disponible para reembolso — la sala no debe finalizar su cancelación', [
                        'room_id' => $room->id,
                        'user_id' => $participant->user_id,
                    ]);
                    throw new \RuntimeException("No hay wallet activa para reembolsar al participante {$participant->user_id} de la sala {$room->id}.");
                }

                // Identificar el gasto exacto para conservar la separación entre
                // bonus_balance y saldo retirable. Sin el movimiento original no
                // se puede inferir la porción de bono de forma segura: fallar y
                // dejar la sala en cancelling es preferible a convertir bono en
                // dinero retirable.
                $originalEntry = WalletMovement::where('user_id', $participant->user_id)
                    ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
                    ->where('amount', $participant->paid_amount)
                    ->where('metadata->room_id', (string) $room->id)
                    ->latest('created_at')
                    ->first();

                if (! $originalEntry || ! array_key_exists('bonus_amount', $originalEntry->metadata ?? [])) {
                    Log::error('[TriviaRoomRefundService] No se encontró el movimiento original completo; se detiene el reembolso', [
                        'room_id' => $room->id,
                        'user_id' => $participant->user_id,
                        'paid_amount' => (float) $participant->paid_amount,
                    ]);
                    throw new \RuntimeException("No se pudo verificar la composición de la entrada del participante {$participant->user_id} en la sala {$room->id}.");
                }

                $bonusPortion = (float) $originalEntry->metadata['bonus_amount'];

                $movement = $this->walletService->recordRefund(
                    wallet: $wallet,
                    amount: (float) $participant->paid_amount,
                    gameId: (string) $room->id,
                    description: "Reembolso Trivia: sala {$room->title} ({$room->code})",
                    bonusPortion: $bonusPortion,
                    idempotencyKey: "trivia_refund:{$room->id}:{$participant->user_id}",
                );

                // Notificar y contar únicamente cuando se creó el movimiento.
                if ($movement->wasRecentlyCreated) {
                    $this->notificationService->create(
                        userId: (string) $participant->user_id,
                        type: 'SYSTEM',
                        title: '↩️ Trivia cancelada — reembolso aplicado',
                        message: $reasonMessage,
                        data: [
                            'room_id' => (string) $room->id,
                            'room_code' => $room->code,
                            'amount' => (float) $participant->paid_amount,
                        ],
                    );

                    $refundedCount++;
                }
            });
        }

        return $refundedCount;
    }
}
