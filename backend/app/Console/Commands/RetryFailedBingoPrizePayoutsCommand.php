<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Services\Commands\CommandService;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Reintenta acreditar premios de bingo que quedaron en prize_status = 'waiting'
 * tras un fallo de BingoPrizePayoutService::payout() (wallet inactiva, error
 * transitorio, etc. — ver BingoDrawService::claimCardWin()).
 *
 * Sin este comando, un premio que falla al pagarse queda esperando para
 * siempre: claimCardWin() solo invoca el pago una vez, en el momento del
 * sorteo, y nunca vuelve a intentarlo por su cuenta.
 */
class RetryFailedBingoPrizePayoutsCommand extends Command
{
    /** Tope de reintentos por premio para no insistir indefinidamente en fallos permanentes. */
    private const MAX_ATTEMPTS = 5;

    protected $signature   = 'bingo:retry-failed-prize-payouts';
    protected $description = 'Reintenta acreditar a la wallet los premios de bingo cuyo pago automático falló';

    public function handle(BingoPrizePayoutService $payoutService): void
    {
        $status = 'SUCCESS';
        $retried = 0;
        $skipped = 0;

        try {
            $cardPrizes = BingoCardPrize::waiting()
                ->withCount('payments')
                ->having('payments_count', '<', self::MAX_ATTEMPTS)
                ->get();

            foreach ($cardPrizes as $cardPrize) {
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
