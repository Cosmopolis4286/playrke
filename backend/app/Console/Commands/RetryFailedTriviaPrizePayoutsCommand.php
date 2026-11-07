<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaScore;
use App\Domains\Games\Trivia\Services\PrizeDistributionService;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reintenta acreditar premios de trivia que quedaron sin pagar tras un fallo
 * de PrizeDistributionService::distribute() (wallet inactiva, error
 * transitorio, o la distribución completa falló y GameEngineService::finishGame()
 * solo lo registró en log — ver el catch alrededor de "Distribuir premios").
 *
 * Se apoya en trivia_scores (ranking final, siempre persistido antes de
 * distribuir premios) en vez de trivia_participants.final_rank, porque este
 * último solo se llena si el pago llegó a intentarse — si distribute() falló
 * por completo, ningún participante tendría final_rank aunque sí ganó.
 *
 * El backoff/reintento en sí lo maneja GamePrizeService::payout() (FSM
 * waiting→processing→claimed) — este comando solo decide QUÉ ganadores
 * necesitan que se llame payoutOne(), no CUÁNDO reintentar cada uno.
 *
 * Premios que quedaron en 'processing' con prize_won = 0 (bug previo en el que
 * finishGame() escribía final_rank antes de distribute() y payoutOne() nunca
 * asignaba el monto): payoutOne() recalcula y asigna el monto desde
 * resolvePrizePayouts() antes de reintentar, así que este mismo comando los
 * recupera sin intervención manual.
 *
 * --days limita la revisión a partidas creadas en los últimos N días (por
 * defecto 90) para no recorrer toda la historia en cada corrida del cron.
 * Para recuperar premios más antiguos: --days=0 (sin límite) en una corrida manual.
 */
class RetryFailedTriviaPrizePayoutsCommand extends Command
{
    protected $description = 'Reintenta acreditar a la wallet los premios de trivia cuyo pago automático falló';
    protected $signature = 'trivia:retry-failed-prize-payouts
                            {--days=90 : Solo revisa partidas creadas en los últimos N días (0 = sin límite)}';

    public function handle(PrizeDistributionService $prizeService) : void
    {
        $status = 'SUCCESS';
        $retried = 0;
        $skipped = 0;

        try {
            $days = max(0, (int) $this->option('days'));

            $gameIds = TriviaGame::where('status', 'finished')
                ->when($days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)))
                ->pluck('room_id', 'id');

            foreach ($gameIds as $gameId => $roomId) {
                $room = TriviaRoom::find($roomId);

                if (! $room || (float) $room->prize_amount <= 0 && (float) $room->sponsor_prize <= 0 && $room->prize_model !== 'pool') {
                    continue;
                }

                $winnerScores = TriviaScore::where('game_id', (string) $gameId)
                    ->whereNotNull('position')
                    ->orderBy('position')
                    ->get(['player_id', 'position', 'correct_answers']);

                if ($winnerScores->isEmpty()) {
                    continue;
                }

                $payouts = $prizeService->resolvePrizePayouts($room, $winnerScores->count());
                $currencyId = (string) $room->prize_currency_id;

                foreach ($payouts as $payout) {
                    $score = $winnerScores->firstWhere('position', $payout['rank']);

                    if (! $score || (int) $score->correct_answers === 0) {
                        continue;
                    }

                    $amount = round((float) $payout['amount'], 8);
                    if ($amount <= 0) {
                        continue;
                    }

                    $participant = TriviaParticipant::where('room_id', $roomId)
                        ->where('user_id', $score->player_id)
                        ->first();

                    // isDisputed() excluido a propósito: un premio revertido por
                    // disputa (ver GamePrizeService::disputeAndReverse()) NUNCA
                    // debe volver a pagarse automáticamente — sin este chequeo,
                    // este comando (cron cada 5 min) re-acreditaría un premio que
                    // un admin acaba de revertir manualmente por fraude detectado.
                    if (! $participant || $participant->isClaimed() || $participant->isCancelled() || $participant->isDisputed()) {
                        continue;
                    }

                    try {
                        $result = $prizeService->payoutOne($room, (string) $score->player_id, $payout['rank'], $amount, $currencyId);

                        if ($result) {
                            $retried++;
                            $this->info("Premio de trivia acreditado: sala {$room->code}, jugador {$score->player_id}.");
                        } else {
                            $skipped++;
                        }
                    } catch (Throwable $e) {
                        $skipped++;
                        Log::error('[Trivia:RetryPrizePayouts] Error al reintentar pago', [
                            'room_id' => (string) $roomId,
                            'player_id' => (string) $score->player_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $output = "Revisión completada. Premios acreditados: {$retried}. Aún pendientes: {$skipped}.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            );
            $this->error($output);
        }

        // Envuelto en try/catch: si logCommandExecution() fallara por
        // cualquier motivo, el error visible al operador debe ser el
        // original de la tarea, no uno de logging que lo enmascare.
        try {
            app(CommandService::class)->logCommandExecution((string) $this->getName(), $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
