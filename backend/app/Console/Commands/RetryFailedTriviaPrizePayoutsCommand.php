<?php

namespace App\Console\Commands;

use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
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
 * Usa PrizePayoutRetryPolicy — misma política que los comandos de Bingo.
 */
class RetryFailedTriviaPrizePayoutsCommand extends Command
{
    protected $signature   = 'trivia:retry-failed-prize-payouts';
    protected $description = 'Reintenta acreditar a la wallet los premios de trivia cuyo pago automático falló';

    public function handle(PrizeDistributionService $prizeService): void
    {
        $status  = 'SUCCESS';
        $retried = 0;
        $skipped = 0;

        try {
            $gameIds = TriviaGame::where('status', 'finished')
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

                $payouts    = $prizeService->resolvePrizePayouts($room, $winnerScores->count());
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

                    if (! $participant || $participant->prize_status === 'paid') {
                        continue;
                    }

                    if (PrizePayoutRetryPolicy::shouldAbandon((int) $participant->prize_payment_attempts)) {
                        $skipped++;
                        continue;
                    }

                    if (! PrizePayoutRetryPolicy::isReadyToRetry($participant->prize_last_attempted_at, (int) $participant->prize_payment_attempts)) {
                        $skipped++;
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
                            'room_id'   => (string) $roomId,
                            'player_id' => (string) $score->player_id,
                            'error'     => $e->getMessage(),
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
                $e->getLine()
            );
            $this->error($output);
        }

        app(CommandService::class)->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
