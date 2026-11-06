<?php

namespace Tests\Feature\Console;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use App\Domains\Wallet\Models\Wallet;
use App\Models\Country;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetryFailedBingoPrizePayoutsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void
    {
        parent::setUp();

        if (! MovementType::where('code', 'PRIZE')->exists()) {
            MovementType::create([
                'code' => 'PRIZE',
                'label' => 'Premio ganado',
                'is_credit' => true,
                'status' => 'ACTIVE',
            ]);
        }
    }

    public function test_reports_exhausted_prizes_and_continues_processing_eligible_prizes() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $eligibleId = $this->insertPrize('waiting');
            $exhaustedId = $this->insertPrize('processing');

            for ($attempt = 0; $attempt < PrizePayoutRetryPolicy::MAX_ATTEMPTS; $attempt++) {
                BingoCardPrizePayment::create([
                    'card_prize_id' => $exhaustedId,
                    'payment_method' => 'system_payment',
                    'room_id' => '970000000000000001',
                    'creator_user_id' => '970000000000000002',
                    'currency_id' => '970000000000000003',
                    'payment_amount' => 5,
                    'payment_status' => 'failed',
                    'processed_at' => now()->subMinutes(10),
                ]);
            }

            $this->assertSame(PrizePayoutRetryPolicy::MAX_ATTEMPTS, BingoCardPrize::findOrFail($exhaustedId)->payments()->count());
            $this->assertSame(PrizePayoutRetryPolicy::MAX_ATTEMPTS, DB::table('bingo_card_prize_payments')
                ->where('card_prize_id', $exhaustedId)
                ->where('payment_status', 'failed')
                ->whereNull('deleted_at')
                ->count());

            // Verifica la misma consulta de detección que utiliza el comando:
            // solo pagos fallidos no eliminados, asociados a premios pendientes.
            $detectedPrizeIds = DB::table('bingo_card_prize_payments as payments')
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

            $this->assertSame([$exhaustedId], $detectedPrizeIds);

            // Un comprador externo debe quedar suspendido, nunca acreditado
            // automáticamente a la wallet del vendedor.
            $exitCode = Artisan::call('bingo:retry-failed-prize-payouts');
            $commandOutput = Artisan::output();

            // Incluye la salida real en el mensaje de fallo: el comando captura
            // Throwable internamente y puede devolver código 0 aun con error.
            $this->assertSame(0, $exitCode, "Salida del comando Bingo:\\n".$commandOutput);
            $this->assertStringContainsString('Premios acreditados: 0', $commandOutput);
            $this->assertStringContainsString(
                'revisión manual por agotar intentos: 1',
                $commandOutput,
                "Salida inesperada del comando Bingo:\\n".$commandOutput,
            );

            $this->assertSame('suspended', BingoCardPrize::findOrFail($eligibleId)->prize_status);
            $this->assertSame('processing', BingoCardPrize::findOrFail($exhaustedId)->prize_status);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function test_retries_internal_winner_and_credits_wallet_only_once() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $country = Country::create([
                'name' => 'Bingo payout test ' . Str::random(6),
                'iso_code_2' => strtoupper(Str::random(2)),
                'iso_code_3' => strtoupper(Str::random(3)),
            ]);
            $user = User::create([
                'username' => 'bingo_' . Str::random(8),
                'email' => Str::random(10) . '@test.local',
                'password' => bcrypt('password'),
                'country_id' => $country->id,
                'referral_code' => strtoupper(Str::random(8)),
                'status' => 'ACTIVE',
            ]);
            $currency = Currency::create([
                'iso_code' => 'BPT',
                'name' => 'Bingo Payout Test',
                'type' => 'FIAT',
                'country_id' => $country->id,
            ]);
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'currency_id' => $currency->id,
                'balance' => 0,
                'status' => 'ACTIVE',
            ]);

            $prizeId = $this->insertPrize('processing', [
                'creator_user_id' => (string) $user->id,
                'currency_id' => (string) $currency->id,
                'external_player_id' => null,
                'local_amount' => 5,
            ]);

            $this->artisan('bingo:retry-failed-prize-payouts')
                ->expectsOutputToContain('Premios acreditados: 1')
                ->assertExitCode(0);

            $wallet->refresh();
            $this->assertEqualsWithDelta(5.0, (float) $wallet->balance, 0.001);
            $this->assertSame('claimed', BingoCardPrize::findOrFail($prizeId)->prize_status);
            $this->assertDatabaseHas('bingo_card_prize_payments', [
                'card_prize_id' => $prizeId,
                'payment_status' => 'completed',
                'user_id' => (string) $user->id,
            ]);
            $this->assertSame(1, DB::table('wallet_movements')
                ->where('idempotency_key', 'bingo_prize:' . $prizeId)
                ->count());

            // Una segunda ejecución no debe volver a acreditar el premio.
            $this->artisan('bingo:retry-failed-prize-payouts')
                ->expectsOutputToContain('Premios acreditados: 0')
                ->assertExitCode(0);

            $wallet->refresh();
            $this->assertEqualsWithDelta(5.0, (float) $wallet->balance, 0.001);
            $this->assertSame(1, DB::table('wallet_movements')
                ->where('idempotency_key', 'bingo_prize:' . $prizeId)
                ->count());
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function insertPrize(string $status, array $overrides = []) : string
    {
        $id = (string) random_int(971000000000000000, 979999999999999999);
        $cardId = (string) random_int(961000000000000000, 969999999999999999);
        $roundId = (string) random_int(951000000000000000, 959999999999999999);

        DB::table('bingo_card_prizes')->insert(array_merge([
            'id' => $id,
            'room_id' => '970000000000000001',
            'creator_user_id' => '970000000000000002',
            'room_user_id' => '970000000000000004',
            'card_lot_id' => '970000000000000005',
            'winning_card_id' => $cardId,
            'currency_id' => '970000000000000003',
            'round_id' => $roundId,
            'external_player_id' => $status === 'waiting' ? (string) random_int(941000000000000000, 949999999999999999) : null,
            'status' => 'ACTIVE',
            'prize_type' => 'full_house',
            'winning_pattern' => 'full_house',
            'usd_amount' => 5,
            'local_amount' => 5,
            'prize_status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return $id;
    }
}
