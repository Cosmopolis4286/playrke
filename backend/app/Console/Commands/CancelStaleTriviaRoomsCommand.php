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
            // Las salas en estado cancelling son reintentos de ejecuciones
            // previas que fallaron tras reclamar la sala. Nunca se permite que
            // el cron de inicio ni un host arranquen una sala en ese estado.
            $rooms = TriviaRoom::where(function ($q) use ($cutoff) {
                $q->where('status', 'cancelling')
                    ->orWhere(function ($waiting) use ($cutoff) {
                        $waiting->where('status', 'waiting')
                            ->where(function ($stale) use ($cutoff) {
                                $stale->where('scheduled_at', '<=', $cutoff)
                                    ->orWhere(function ($manual) use ($cutoff) {
                                        $manual->whereNull('scheduled_at')
                                            ->where('created_at', '<=', $cutoff);
                                    });
                            });
                    });
            })->get(['id']);

            foreach ($rooms as $candidate) {
                $roomId = (string) $candidate->id;

                try {
                    // Fase 1: reclamar la sala y confirmar 'cancelling' antes
                    // de tocar wallets en MySQL. Si el proceso cae después, el
                    // siguiente cron puede reintentar sin que la sala arranque.
                    $claimed = DB::connection('pgsql_trivia')->transaction(function () use ($roomId, $cutoff) {
                        $room = DB::connection('pgsql_trivia')
                            ->table('trivia_rooms')
                            ->where('id', $roomId)
                            ->lockForUpdate()
                            ->first();

                        if (! $room || ! in_array((string) $room->status, ['waiting', 'cancelling'], true)) {
                            return false;
                        }

                        if ((string) $room->status === 'waiting') {
                            $isStale = $room->scheduled_at !== null
                                ? \\Illuminate\\Support\\Carbon::parse($room->scheduled_at)->lte($cutoff)
                                : \\Illuminate\\Support\\Carbon::parse($room->created_at)->lte($cutoff);

                            if (! $isStale) {
                                return false;
                            }

                            DB::connection('pgsql_trivia')
                                ->table('trivia_rooms')
                                ->where('id', $roomId)
                                ->update(['status' => 'cancelling', 'updated_at' => now()]);
                        }

                        return true;
                    });

                    if (! $claimed) {
                        continue;
                    }

                    // Fase 2: procesar bajo el mismo bloqueo que usa el inicio
                    // de partida. Reintentos de reembolso son seguros por clave
                    // idempotente; una sala solo pasa a cancelled al completar.
                    $result = DB::connection('pgsql_trivia')->transaction(function () use ($roomId, $refundService) {
                        $locked = DB::connection('pgsql_trivia')
                            ->table('trivia_rooms')
                            ->where('id', $roomId)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked || (string) $locked->status !== 'cancelling') {
                            return ['state' => 'skipped', 'refunded' => 0, 'code' => null];
                        }

                        $room = TriviaRoom::findOrFail($roomId);
                        $reasonMessage = "La sala '{$room->title}' ({$room->code}) fue cancelada por falta de actividad. Se te reembolsó tu entrada.";
                        $roomRefunded = $refundService->refundConfirmedParticipants($room, $reasonMessage);

                        DB::connection('pgsql_trivia')
                            ->table('trivia_rooms')
                            ->where('id', $roomId)
                            ->update(['status' => 'cancelled', 'updated_at' => now()]);

                        return ['state' => 'cancelled', 'refunded' => $roomRefunded, 'code' => $room->code];
                    });

                    if ($result['state'] === 'cancelled') {
                        $cancelled++;
                        $refunded += $result['refunded'];
                        $this->info("Sala {$result['code']} cancelada — {$result['refunded']} reembolso(s) nuevo(s).");
                    }
                } catch (Throwable $e) {
                    $errors++;
                    Log::error('[CancelStaleTriviaRooms] Error al cancelar sala; queda en cancelling para reintento seguro', [
                        'room_id' => $roomId,
                        'error'   => $e->getMessage(),
                    ]);
                    $this->warn("  ✗ Error en sala {$roomId}: {$e->getMessage()}");
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
