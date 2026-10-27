<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
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

        try {
            // 'processing' incluido: cubre premios que quedaron a medias (wallet
            // posiblemente ya acreditada) en una corrida anterior — payout() ya
            // sabe distinguir ese caso y solo reparar el estado sin re-acreditar.
            //
            // chunkById(50): evita cargar toda la tabla en memoria si hay muchos
            // premios pendientes tras un incidente prolongado.
            //
            // whereRaw con subquery en lugar de withCount+having: HAVING sin GROUP BY
            // es SQL inválido en MySQL ONLY_FULL_GROUP_BY (default desde 5.7) y
            // silenciosamente devuelve cero filas — nunca reintentaría ningún premio.
            BingoCardPrize::whereIn('prize_status', ['waiting', 'processing'])
                ->whereRaw(
                    '(SELECT COUNT(*) FROM bingo_card_prize_payments WHERE bingo_card_prize_payments.card_prize_id = bingo_card_prizes.id AND bingo_card_prize_payments.deleted_at IS NULL) < ?',
                    [PrizePayoutRetryPolicy::MAX_ATTEMPTS],
                )
                ->chunkById(50, function($chunk) use ($payoutService, &$retried, &$skipped) {
                    foreach ($chunk as $cardPrize) {
                        // Cargar solo el último payment de cada modelo individualmente:
                        // limit(1) en eager-load HasMany es un pitfall conocido de Laravel
                        // que limita el resultado TOTAL de la relación a 1 fila global, no
                        // 1 por modelo — todos los demás modelos verían payments vacíos y
                        // bypassearían el backoff.
                        $lastPayment = $cardPrize->payments()->latest('created_at')->first();
                        $attempts = $cardPrize->payments()->count();

                        if (! PrizePayoutRetryPolicy::isReadyToRetry($lastPayment?->created_at, $attempts)) {
                            $skipped++;

                            continue;
                        }

                        $payoutService->payout($cardPrize);

                        $cardPrize->refresh();

                        if ($cardPrize->isClaimed()) {
                            $retried++;
                            $this->info("Premio #{$cardPrize->id} acreditado exitosamente.");
                        } else {
                            $skipped++;
                        }
                    }
                });

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
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
