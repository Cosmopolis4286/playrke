<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizeReviewService;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use App\Domains\Wallet\Models\Wallet;
use App\Models\Country;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * D4/D5: premios que el pago automático no resuelve (suspendidos o con
 * reintentos agotados) tienen salida: reintento, entrega fuera del sistema
 * con referencia, o anulación con motivo.
 */
class BingoPrizeReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    private BingoPrizeReviewService $service;

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (! MovementType::where('code', 'PRIZE')->exists()) {
            MovementType::create(['code' => 'PRIZE', 'label' => 'Premio ganado', 'is_credit' => true, 'status' => 'ACTIVE']);
        }
        MovementType::forgetCatalogCache();

        $this->service = app(BingoPrizeReviewService::class);
    }

    protected function tearDown() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    public function test_cancel_requires_a_reviewable_prize_and_never_touches_processing() : void
    {
        $processing = $this->insertPrize('processing');
        $this->failAttempts($processing, PrizePayoutRetryPolicy::MAX_ATTEMPTS);
        $waiting = $this->insertPrize('waiting');
        $this->failAttempts($waiting, PrizePayoutRetryPolicy::MAX_ATTEMPTS);

        $this->assertSame('cancelled', $this->service->cancel($waiting, '1', 'Ganador renunció al premio')->prize_status);

        try {
            $this->service->cancel($processing, '1', 'Intento de anular');
            $this->fail('No debe anular un premio en processing');
        } catch (DomainException) {
            $this->assertSame('processing', BingoCardPrize::findOrFail($processing)->prize_status);
        }

        $fresh = $this->insertPrize('waiting');
        $this->expectException(DomainException::class);
        $this->service->cancel($fresh, '1', 'No está en revisión');
    }

    public function test_external_winner_cannot_be_retried_to_a_wallet() : void
    {
        $id = $this->insertPrize('suspended', ['external_player_id' => '941000000000000001']);

        $this->expectException(DomainException::class);
        $this->service->retry($id, '1');
    }

    public function test_external_winner_prize_is_marked_delivered_with_reference() : void
    {
        $id = $this->insertPrize('suspended', ['external_player_id' => '941000000000000001']);

        $prize = $this->service->markDelivered($id, '1', 'PM-778899', 'Pago móvil al ganador');

        $this->assertSame('claimed', $prize->prize_status);
        $payment = BingoCardPrizePayment::where('card_prize_id', $id)->sole();
        $this->assertSame('completed', $payment->payment_status);
        $this->assertSame('seller_cash', $payment->payment_method);
        $this->assertSame('PM-778899', $payment->transaction_reference);
        $this->assertNull($payment->user_id, 'No se atribuye al vendedor');
        $this->assertStringContainsString('PM-778899', (string) $prize->prize_notes);

        // Ya resuelto: no se puede registrar dos veces.
        $this->expectException(DomainException::class);
        $this->service->markDelivered($id, '1', 'PM-2');
    }

    public function test_queue_lists_suspended_and_exhausted_prizes_only() : void
    {
        $external = $this->insertPrize('suspended', ['external_player_id' => '941000000000000001']);
        $noWallet = $this->insertPrize('suspended');
        $exhausted = $this->insertPrize('waiting');
        $this->failAttempts($exhausted, PrizePayoutRetryPolicy::MAX_ATTEMPTS);
        $retrying = $this->insertPrize('waiting');
        $this->failAttempts($retrying, 1);

        $queue = $this->service->pending()->mapWithKeys(fn (array $i) => [(string) $i['prize']->id => $i['reason']])->all();

        $expected = [
            $external => BingoPrizeReviewService::REASON_EXTERNAL,
            $noWallet => BingoPrizeReviewService::REASON_NO_WALLET,
            $exhausted => BingoPrizeReviewService::REASON_EXHAUSTED,
        ];
        ksort($expected);
        ksort($queue);
        $this->assertSame($expected, $queue);
    }

    public function test_retry_pays_a_suspended_winner_once_the_wallet_exists() : void
    {
        [$user, $currency] = $this->userWithCurrency();
        $id = $this->insertPrize('suspended', ['creator_user_id' => (string) $user->id, 'currency_id' => (string) $currency->id, 'local_amount' => 7]);
        $wallet = Wallet::create(['user_id' => $user->id, 'currency_id' => $currency->id, 'balance' => 0, 'status' => 'ACTIVE']);

        $prize = $this->service->retry($id, '1');

        $this->assertSame('claimed', $prize->prize_status);
        $this->assertEqualsWithDelta(7.0, (float) $wallet->fresh()->balance, 0.001);
    }

    private function failAttempts(string $prizeId, int $n) : void
    {
        for ($i = 0; $i < $n; $i++) {
            BingoCardPrizePayment::create([
                'card_prize_id' => $prizeId,
                'payment_method' => 'system_payment',
                'room_id' => '970000000000000001',
                'creator_user_id' => '970000000000000002',
                'currency_id' => '970000000000000003',
                'payment_amount' => 5,
                'payment_status' => 'failed',
                'payment_notes' => 'Falla simulada',
                'processed_at' => now()->subMinutes(10),
            ]);
        }
    }

    private function insertPrize(string $status, array $overrides = []) : string
    {
        $id = (string) random_int(971000000000000000, 979999999999999999);

        DB::table('bingo_card_prizes')->insert(array_merge([
            'id' => $id,
            'room_id' => '970000000000000001',
            'creator_user_id' => '970000000000000002',
            'room_user_id' => '970000000000000004',
            'card_lot_id' => (string) random_int(931000000000000000, 939999999999999999),
            'winning_card_id' => (string) random_int(961000000000000000, 969999999999999999),
            'currency_id' => '970000000000000003',
            'round_id' => (string) random_int(951000000000000000, 959999999999999999),
            'external_player_id' => null,
            'status' => 'ACTIVE',
            'prize_type' => 'full_house',
            'winning_pattern' => 'full_house',
            'usd_amount' => 5,
            'local_amount' => 5,
            'prize_status' => $status,
            'created_at' => now()->subMinutes(30),
            'updated_at' => now(),
        ], $overrides));

        return $id;
    }

    /** @return array{0: User, 1: Currency} */
    private function userWithCurrency() : array
    {
        $country = Country::create(['name' => 'Review ' . Str::random(6), 'iso_code_2' => strtoupper(Str::random(2)), 'iso_code_3' => strtoupper(Str::random(3))]);
        $user = User::create([
            'username' => 'rev_' . Str::random(8), 'email' => Str::random(10) . '@test.local', 'password' => bcrypt('password'),
            'country_id' => $country->id, 'referral_code' => strtoupper(Str::random(8)), 'status' => 'ACTIVE',
        ]);
        $currency = Currency::create(['iso_code' => 'RVT', 'name' => 'Review Test', 'type' => 'FIAT', 'country_id' => $country->id]);

        return [$user, $currency];
    }
}
