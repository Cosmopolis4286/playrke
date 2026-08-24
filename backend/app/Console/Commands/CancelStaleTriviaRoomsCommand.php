<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\WalletMovement;
use App\Services\Commands\CommandService;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cancela salas de trivia 'waiting' abandonadas (scheduled_at vencido hace
 * mucho, o nunca tuvo scheduled_at, sin nadie inscrito o inscritos que
 * nunca llegaron a jugar) y reembolsa a cada participante confirmado su
 * entrada pagada.
 *
 * Antes de este comando, StartScheduledTriviaRoomsCommand solo omitía
 * (skip) estas salas cada minuto indefinidamente sin actuar — dado que
 * JoinRoomAction::chargeEntryFee() sí descuenta dinero real de wallet al
 * inscribirse, una sala que nunca arranca deja ese dinero retenido sin
 * resolución hasta que un admin intervenga a mano.
 */
class CancelStaleTriviaRoomsCommand extends Command
{
    protected $signature = 'trivia:cancel-stale-rooms';

    protected $description = 'Cancela salas de trivia "waiting" abandonadas y reembolsa a los participantes confirmados';

    /** Tiempo tras el cual una sala 'waiting' sin arrancar se considera abandonada. */
    private const STALE_AFTER_MINUTES = 30;

    public function handle(WalletService $walletService, NotificationService $notificationService): void
    {
        $status    = 'SUCCESS';
        $cancelled = 0;
        $refunded  = 0;
        $errors    = 0;

        try {
            $cutoff = now()->subMinutes(self::STALE_AFTER_MINUTES);

            // Dos orígenes de "abandonada": scheduled_at vencido hace rato
            // (StartScheduledTriviaRoomsCommand ya la vio y la saltó por
            // falta de jugadores, o sí tenía pero nunca se pudo iniciar),
            // o sin scheduled_at (inicio manual) y el host nunca la arrancó.
            $rooms = TriviaRoom::where('status', 'waiting')
                ->where(function ($q) use ($cutoff) {
                    $q->where('scheduled_at', '<=', $cutoff)
                        ->orWhere(function ($q2) use ($cutoff) {
                            $q2->whereNull('scheduled_at')->where('created_at', '<=', $cutoff);
                        });
                })
                ->get();

            foreach ($rooms as $room) {
                try {
                    $roomRefunded = $this->cancelAndRefundRoom($room, $walletService, $notificationService);
                    $cancelled++;
                    $refunded += $roomRefunded;
                    $this->info("Sala {$room->code} cancelada — {$roomRefunded} reembolso(s) aplicado(s).");
                } catch (Throwable $e) {
                    $errors++;
                    Log::error('[CancelStaleTriviaRooms] Error al cancelar sala', [
                        'room_id' => $room->id,
                        'error'   => $e->getMessage(),
                    ]);
                    $this->warn("  ✗ Error en sala {$room->code}: {$e->getMessage()}");
                }
            }

            $output = "Salas abandonadas: {$cancelled} canceladas, {$refunded} reembolsos aplicados, {$errors} con errores.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            $this->error($output);
        }

        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }

    /**
     * Cancela una sala y reembolsa a cada participante confirmado con
     * paid_amount > 0. Transacción por sala (no por lote completo): un
     * fallo en una sala no debe afectar los reembolsos ya aplicados de
     * otra. trivia_participants vive en la conexión pgsql_trivia (cross-
     * database, mismo patrón que JoinRoomAction).
     *
     * @return int Cantidad de participantes reembolsados en esta sala.
     */
    private function cancelAndRefundRoom(TriviaRoom $room, WalletService $walletService, NotificationService $notificationService): int
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
            DB::transaction(function () use ($room, $participant, $walletService, $notificationService, &$refundedCount) {
                $wallet = $walletService->findActiveWallet(
                    (string) $participant->user_id,
                    (string) $participant->paid_currency_id,
                );

                if (! $wallet) {
                    Log::warning('[CancelStaleTriviaRooms] Wallet no disponible para reembolso — se omite este participante', [
                        'room_id' => $room->id,
                        'user_id' => $participant->user_id,
                    ]);
                    return;
                }

                // La entrada original se registró como TRIVIA_ENTRY con
                // metadata.bonus_amount (ver WalletService::recordTriviaEntry())
                // — se devuelve esa misma proporción a bonus_balance para no
                // convertir bono en saldo retirable al cancelar.
                $originalEntry = WalletMovement::where('user_id', $participant->user_id)
                    ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
                    ->where('amount', $participant->paid_amount)
                    ->latest('created_at')
                    ->first();

                $bonusPortion = (float) ($originalEntry?->metadata['bonus_amount'] ?? 0.0);

                $walletService->recordRefund(
                    wallet:       $wallet,
                    amount:       (float) $participant->paid_amount,
                    gameId:       (string) $room->id,
                    description:  "Reembolso Trivia: sala {$room->title} cancelada por abandono ({$room->code})",
                    bonusPortion: $bonusPortion,
                );

                $notificationService->create(
                    userId: (string) $participant->user_id,
                    type: 'SYSTEM',
                    title: '↩️ Trivia cancelada — reembolso aplicado',
                    message: "La sala '{$room->title}' ({$room->code}) fue cancelada por falta de actividad. Se te reembolsó tu entrada.",
                    data: [
                        'room_id' => (string) $room->id,
                        'room_code' => $room->code,
                        'amount' => (float) $participant->paid_amount,
                    ],
                );

                $refundedCount++;
            });
        }

        $room->update(['status' => 'cancelled']);

        return $refundedCount;
    }
}
