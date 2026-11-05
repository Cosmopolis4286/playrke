<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reintenta acreditar premios de bingo que quedaron en prize_status =
 * 'waiting'/'processing' tras un fallo de BingoPrizePayoutService::payout()
 * (wallet inactiva, error transitorio, etc. — ver
 * BingoDrawService::claimCardWin()).
 *
 * Sin este comando, un premio que falla al pagarse queda esperando para
 * siempre: claimCardWin() solo invoca el pago una vez, en el momento del
 * sorteo, y nunca vuelve a intentarlo por su cuenta.
 *
 * Usa PrizePayoutRetryPolicy (MAX_ATTEMPTS + backoff lineal) — la misma
 * política que RetryFailedBingoRefundsCommand y trivia:retry-failed-prize-payouts
 * — en vez de una constante local y un reintento sin espera entre corridas.
 */
class RetryFailedBingoPrizePayoutsCommand extends Command
{
    protected $description = 'Reintenta acreditar a la wallet los premios de bingo cuyo pago automático falló';
    protected $signature = 'bingo:retry-failed-prize-payouts';

    public function handle(BingoPrizePayoutService $payoutService) : void
    {
        $status = 'SUCCESS';
        $retried = 0;
        $skipped = 0;
        $exhausted = 0;

        try {
            // Los premios que agotaron intentos no se modifican automáticamente:
            // podrían requerir conciliación si el worker cayó en una ventana ambigua.
            // Agrupa intentos persistidos por premio. La subconsulta filtra
            // explícitamente SoftDeletes y evita diferencias entre el conteo
            // de la relación y el SQL agregado generado por has().
            $exhaustedPrizeIds = DB::table('bingo_card_prize_payments')
                ->select('card_prize_id')
                ->where('payment_status', 'failed')
                ->whereNull('deleted_at')
                ->groupBy('card_prize_id')
                ->havingRaw('COUNT(*) >= ?', [PrizePayoutRetryPolicy::MAX_ATTEMPTS])
                ->pluck('card_prize_id')
                ->map(static fn ($id) => (string) $id)
                ->all();

            // Cuenta desde la tabla para no depender de scopes globales del modelo
            // (por ejemplo, ámbitos de organización) que pueden ocultar premios
            // que sí deben aparecer en el reporte operativo de agotados.
            $exhausted = $exhaustedPrizeIds === []
                ? 0
                : DB::table('bingo_card_prizes')
                    ->whereIn('prize_status', ['waiting', 'processing'])
                    ->whereIn('id', $exhaustedPrizeIds)
                    ->whereNull('deleted_at')
                    ->count();

            if ($exhausted > 0) {
                Log::warning('[Bingo:RetryPrizePayouts] Premios agotaron el máximo de intentos y requieren revisión manual', [
                    'count' => $exhausted,
                    'max_attempts' => PrizePayoutRetryPolicy::MAX_ATTEMPTS,
                ]);
            }

            // 'processing' incluido: cubre premios que quedaron a medias (wallet
            // posiblemente ya acreditada) en una corrida anterior — payout() ya
            // sabe distinguir ese caso y solo reparar el estado sin re-acreditar.
            //
            // chunkById(50): evita cargar toda la tabla en memoria si hay muchos
            // premios pendientes tras un incidente prolongado.
            //
            // La subconsulta excluye premios que ya alcanzaron el límite de intentos.
            BingoCardPrize::whereIn('prize_status', ['waiting', 'processing'])
                ->when($exhaustedPrizeIds !== [], fn ($query) => $query->whereNotIn('id', $exhaustedPrizeIds))
                ->chunkById(50, function($chunk) use ($payoutService, &$retried, &$skipped) {
                    foreach ($chunk as $cardPrize) {
                        // Cargar solo el último payment de cada modelo individualmente:
                        // limit(1) en eager-load HasMany es un pitfall conocido de Laravel
                        // que limita el resultado TOTAL de la relación a 1 fila global, no
                        // 1 por modelo — todos los demás modelos verían payments vacíos y
                        // bypassearían el backoff.
                        $lastPayment = $cardPrize->payments()
                            ->where('payment_status', 'failed')
                            ->latest('created_at')
                            ->first();
                        $attempts = $cardPrize->payments()
                            ->where('payment_status', 'failed')
                            ->count();

                        if (! PrizePayoutRetryPolicy::isReadyToRetry($lastPayment?->created_at, $attempts)) {
                            $skipped++;

                            continue;
                        }

                        try {
                            $payoutService->payout($cardPrize);

                            $cardPrize->refresh();

                            if ($cardPrize->isClaimed()) {
                                $retried++;
                                $this->info("Premio #{$cardPrize->id} acreditado exitosamente.");
                            } else {
                                $skipped++;
                            }
                        } catch (Throwable $e) {
                            // Un premio defectuoso no debe detener la recuperación
                            // de los siguientes registros del mismo lote.
                            $skipped++;
                            Log::error('[Bingo:RetryPrizePayouts] Error al reintentar un premio', [
                                'card_prize_id' => (string) $cardPrize->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });

            $output = "Revisión completada. Premios acreditados: {$retried}. Aún pendientes: {$skipped}. Requieren revisión manual por agotar intentos: {$exhausted}.";
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
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
