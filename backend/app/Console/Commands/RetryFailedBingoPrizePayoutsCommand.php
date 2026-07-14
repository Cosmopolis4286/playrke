<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Services\Commands\CommandService;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutRetryPolicy;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
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
 * Usa BingoPrizePayoutRetryPolicy (MAX_ATTEMPTS + backoff lineal) — la misma
 * política que RetryFailedBingoRefundsCommand — en vez de una constante local
 * y un reintento sin espera entre corridas.
 */
class RetryFailedBingoPrizePayoutsCommand extends Command
{
    protected $signature   = 'bingo:retry-failed-prize-payouts';
    protected $description = 'Reintenta acreditar a la wallet los premios de bingo cuyo pago automático falló';

    public function handle(BingoPrizePayoutService $payoutService): void
    {
        $status = 'SUCCESS';
        $retried = 0;
        $skipped = 0;

        try {
            // 'processing' incluido: cubre premios que quedaron a medias (wallet
            // posiblemente ya acreditada) en una corrida anterior — payout() ya
            // sabe distinguir ese caso y solo reparar el estado sin re-acreditar.
            $cardPrizes = BingoCardPrize::whereIn('prize_status', ['waiting', 'processing'])
                ->withCount('payments')
                ->having('payments_count', '<', BingoPrizePayoutRetryPolicy::MAX_ATTEMPTS)
                ->with(['payments' => fn ($q) => $q->latest('created_at')->limit(1)])
                ->get();

            foreach ($cardPrizes as $cardPrize) {
                $lastPayment = $cardPrize->payments->first();
                $attempts    = $cardPrize->payments_count;

                if (!BingoPrizePayoutRetryPolicy::isReadyToRetry($lastPayment?->created_at, $attempts)) {
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
