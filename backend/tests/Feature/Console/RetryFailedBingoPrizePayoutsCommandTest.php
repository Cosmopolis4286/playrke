<?php

namespace Tests\Feature\Console;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetryFailedBingoPrizePayoutsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_exhausted_prizes_and_continues_processing_eligible_prizes() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $eligibleId = $this->insertPrize('waiting');
            $exhaustedId = $this->insertPrize('processing');

            for ($attempt = 0; $attempt < 5; $attempt++) {
                DB::table('bingo_card_prize_payments')->insert([
                    'id' => (string) (970000000000000100 + $attempt),
                    'card_prize_id' => $exhaustedId,
                    'payment_method' => 'system_payment',
                    'room_id' => '970000000000000001',
                    'creator_user_id' => '970000000000000002',
                    'currency_id' => '970000000000000003',
                    'payment_amount' => 5,
                    'payment_status' => 'failed',
                    'created_at' => now()->subMinutes(10),
                    'updated_at' => now()->subMinutes(10),
                ]);
            }

            // Usa el servicio real: BingoPrizePayoutService es final y un
            // comprador externo debe quedar suspendido, nunca acreditado
            // automáticamente a la wallet del vendedor.
            $this->artisan('bingo:retry-failed-prize-payouts')
                ->expectsOutputToContain('Premios acreditados: 0')
                ->expectsOutputToContain('Aún pendientes: 0')
                ->expectsOutputToContain('revisión manual por agotar intentos: 1')
                ->assertExitCode(0);

            $this->assertSame('suspended', BingoCardPrize::findOrFail($eligibleId)->prize_status);
            $this->assertSame('processing', BingoCardPrize::findOrFail($exhaustedId)->prize_status);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function insertPrize(string $status) : string
    {
        $id = (string) random_int(971000000000000000, 979999999999999999);
        $cardId = (string) random_int(961000000000000000, 969999999999999999);
        $roundId = (string) random_int(951000000000000000, 959999999999999999);

        DB::table('bingo_card_prizes')->insert([
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
        ]);

        return $id;
    }
}
