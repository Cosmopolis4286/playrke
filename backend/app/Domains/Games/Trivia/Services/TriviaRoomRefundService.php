<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\WalletMovement;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reembolsa la entrada pagada por cada participante confirmado de una sala
 * de Trivia. Compartido por dos casos de uso:
 *
 *  - CancelStaleTriviaRoomsCommand: salas 'waiting' abandonadas (cron).
 *  - TriviaRoomAdminController::forceClose()/destroy(): un admin cierra una
 *    sala manualmente. Antes de este servicio, estos dos endpoints solo
 *    actualizaban el status y limpiaban Redis, sin reembolsar el entry fee
 *    ya cobrado — un admin forzando el cierre de una sala 'playing' con
 *    jugadores que ya pagaron dejaba ese dinero cobrado sin partida jugada
 *    y sin ningún mecanismo de recuperación, ni siquiera manual.
 *
 * Extraído de CancelStaleTriviaRoomsCommand (ver ese comando para el caso
 * de salas abandonadas por scheduling).
 */
class TriviaRoomRefundService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Reembolsa a cada participante confirmado con paid_amount > 0.
     * Transacción por participante (no por lote completo): un fallo en un
     * participante no debe afectar los reembolsos ya aplicados a otros.
     * trivia_participants vive en la conexión pgsql_trivia (cross-database,
     * mismo patrón que JoinRoomAction).
     *
     * No cambia el status de la sala — el llamador decide a qué estado pasa
     * (cancelled, closed, etc.) según su propio caso de uso.
     *
     * @return int Cantidad de participantes reembolsados.
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
                    Log::warning('[TriviaRoomRefundService] Wallet no disponible para reembolso — se omite este participante', [
                        'room_id' => $room->id,
                        'user_id' => $participant->user_id,
                    ]);
                    return;
                }

                // La entrada original se registró como TRIVIA_ENTRY con
                // metadata.bonus_amount (ver WalletService::recordTriviaEntry())
                // — se devuelve esa misma proporción a bonus_balance para no
                // convertir bono en saldo retirable al cancelar. Filtrar
                // también por metadata->room_id: si el mismo usuario pagó la
                // misma entrada exacta en dos salas distintas, sin este
                // filtro se podía recuperar el movimiento equivocado.
                $originalEntry = WalletMovement::where('user_id', $participant->user_id)
                    ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
                    ->where('amount', $participant->paid_amount)
                    ->where('metadata->room_id', (string) $room->id)
                    ->latest('created_at')
                    ->first();

                $bonusPortion = (float) ($originalEntry?->metadata['bonus_amount'] ?? 0.0);

                $this->walletService->recordRefund(
                    wallet:       $wallet,
                    amount:       (float) $participant->paid_amount,
                    gameId:       (string) $room->id,
                    description:  "Reembolso Trivia: sala {$room->title} ({$room->code})",
                    bonusPortion: $bonusPortion,
                );

                $this->notificationService->create(
                    userId: (string) $participant->user_id,
                    type: 'SYSTEM',
                    title: '↩️ Trivia cancelada — reembolso aplicado',
                    message: $reasonMessage,
                    data: [
                        'room_id'   => (string) $room->id,
                        'room_code' => $room->code,
                        'amount'    => (float) $participant->paid_amount,
                    ],
                );

                $refundedCount++;
            });
        }

        return $refundedCount;
    }
}
