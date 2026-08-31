<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaRoomRefundService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
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

    public function handle(TriviaRoomRefundService $refundService): void
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
                    $reasonMessage = "La sala '{$room->title}' ({$room->code}) fue cancelada por falta de actividad. Se te reembolsó tu entrada.";
                    $roomRefunded  = $refundService->refundConfirmedParticipants($room, $reasonMessage);
                    $room->update(['status' => 'cancelled']);

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
}
