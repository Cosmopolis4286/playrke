<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaRoomRefundService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cancela salas de trivia 'waiting' abandonadas y reembolsa las entradas pagadas.
 */
class CancelStaleTriviaRoomsCommand extends Command
{
    protected $signature = 'trivia:cancel-stale-rooms';

    protected $description = 'Cancela salas de trivia "waiting" abandonadas y reembolsa a los participantes confirmados';

    private const STALE_AFTER_MINUTES = 30;

    public function handle(TriviaRoomRefundService $refundService): void
    {
        $status = 'SUCCESS';
        $cancelled = 0;
        $refunded = 0;
        $errors = 0;

        try {
            $cutoff = now()->subMinutes(self::STALE_AFTER_MINUTES);

            // 'cancelling' es recuperable: si una ejecución anterior se interrumpió,
            // el próximo cron vuelve a intentar los reembolsos idempotentes.
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
                    // Reclamar y confirmar el estado en PostgreSQL antes de escribir
                    // en la wallet MySQL. El estado impide que la partida arranque.
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
                                ? Carbon::parse($room->scheduled_at)->lte($cutoff)
                                : Carbon::parse($room->created_at)->lte($cutoff);

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

                    // IMPORTANT: do not hold a PostgreSQL transaction while the
                    // refund service writes to MySQL. These databases have no
                    // distributed transaction; a rollback in one cannot undo the
                    // other. The stable idempotency key makes retries safe if the
                    // process fails between wallet refunds and finalization.
                    $room = TriviaRoom::findOrFail($roomId);
                    $currentStatus = DB::connection('pgsql_trivia')
                        ->table('trivia_rooms')
                        ->where('id', $roomId)
                        ->value('status');

                    if ((string) $currentStatus !== 'cancelling') {
                        continue;
                    }

                    $reasonMessage = "La sala '{$room->title}' ({$room->code}) fue cancelada por falta de actividad. Se te reembolsó tu entrada.";
                    $roomRefunded = $refundService->refundConfirmedParticipants($room, $reasonMessage);

                    // Finalizar en una transacción corta. Si falla, la sala queda
                    // en 'cancelling' y el siguiente cron repetirá sin duplicar saldo.
                    $finalized = DB::connection('pgsql_trivia')->transaction(function () use ($roomId) {
                        $locked = DB::connection('pgsql_trivia')
                            ->table('trivia_rooms')
                            ->where('id', $roomId)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked || (string) $locked->status !== 'cancelling') {
                            return false;
                        }

                        DB::connection('pgsql_trivia')
                            ->table('trivia_rooms')
                            ->where('id', $roomId)
                            ->update(['status' => 'cancelled', 'updated_at' => now()]);

                        return true;
                    });

                    if ($finalized) {
                        $cancelled++;
                        $refunded += $roomRefunded;
                        $this->info("Sala {$room->code} cancelada — {$roomRefunded} reembolso(s) nuevo(s).");
                    }
                } catch (Throwable $e) {
                    $errors++;
                    Log::error('[CancelStaleTriviaRooms] Error al cancelar sala; queda en cancelling para reintento seguro', [
                        'room_id' => $roomId,
                        'error' => $e->getMessage(),
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
