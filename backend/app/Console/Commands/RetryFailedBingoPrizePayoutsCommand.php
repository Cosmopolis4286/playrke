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
            // La fuente de verdad son los pagos fallidos. Agrupar desde pagos
            // evita recorrer premios y comparar BIGINT en PHP registro a registro.
            $exhaustedPrizeIds = DB::table('bingo_card_prize_payments as payments')
                ->join('bingo_card_prizes as prizes', 'prizes.id', '=', 'payments.card_prize_id')
                ->whereIn('prizes.prize_status', ['waiting', 'processing'])
                ->whereNull('prizes.deleted_at')
                ->where('payments.payment_status', 'failed')
                ->whereNull('payments.deleted_at')
                ->select('payments.card_prize_id')
                ->groupBy('payments.card_prize_id')
                ->havingRaw('COUNT(*) >= ?', [PrizePayoutRetryPolicy::MAX_ATTEMPTS])
                ->pluck('payments.card_prize_id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $exhausted = count($exhaustedPrizeIds);
            if ($exhausted > 0) {
                Log::warning('[Bingo:RetryPrizePayouts] Premios agotaron el máximo de intentos y requieren revisión manual', [
                    'count' => $exhausted,
                    'max_attempts' => PrizePayoutRetryPolicy::MAX_ATTEMPTS,
                ]);
            }

            // 'processing' incluido: payout() reconoce estados intermedios y
            // protege la acreditación para evitar duplicados.
            BingoCardPrize::whereIn('prize_status', ['waiting', 'processing'])
                ->when($exhaustedPrizeIds !== [], fn ($query) => $query->whereNotIn('id', $exhaustedPrizeIds))
                ->chunkById(50, function ($chunk) use ($payoutService, &$retried, &$skipped) {
                    foreach ($chunk as $cardPrize) {
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
