<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaScore;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\MovementType;
use App\Models\User;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Services\Games\GamePrizeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Cubre GamePrizeService::disputeAndReverse() usando TriviaParticipant como
 * implementación concreta de GamePrizeContract (mismo patrón de fixtures que
 * RetryFailedTriviaPrizePayoutsCommandTest) — el método es agnóstico de
 * juego, así que verificarlo contra una implementación basta para el
 * contrato compartido con BingoCardPrize.
 */
class GamePrizeServiceDisputeTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp(): void
    {
        parent::setUp();

        // wallet_movements.type es FK contra movement_types.code — sembrado
        // manual en playrke_testing (ver RetryFailedTriviaPrizePayoutsCommandTest).
        foreach (['PRIZE', 'PRIZE_REVERSAL'] as $code) {
            if (! MovementType::where('code', $code)->exists()) {
                MovementType::create([
                    'code' => $code,
                    'label' => $code,
                    'is_credit' => $code === 'PRIZE',
                    'status' => 'ACTIVE',
                ]);
            }
        }
    }

    private function makeUser(): User
    {
        $country = Country::create([
            'name' => 'Test Country ' . Str::random(6),
            'iso_code_2' => strtoupper(Str::random(2)),
            'iso_code_3' => strtoupper(Str::random(3)),
        ]);

        return User::create([
            'username' => 'user_' . Str::random(8),
            'email' => Str::random(10) . '@test.local',
            'password' => bcrypt('password'),
            'country_id' => $country->id,
            'referral_code' => strtoupper(Str::random(8)),
            'status' => 'ACTIVE',
        ]);
    }

    /** Participante con premio ya reclamado ($amount acreditado a la wallet) y su wallet. */
    private function makeClaimedParticipant(float $amount = 50.0, float $walletBalance = 100.0): array
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TST', 'name' => 'Test Coin', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $room = TriviaRoom::create([
            'code' => 'DP' . substr(uniqid(), -6),
            'title' => 'Sala disputa premio',
            'host_id' => '1',
            'status' => 'closed',
            'max_players' => 10,
            'question_count' => 3,
            'prize_currency_id' => (string) $currency->id,
            'prize_model' => 'guaranteed',
            'prize_amount' => $amount,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => $walletBalance,
            'status' => 'ACTIVE',
        ]);

        $participant = TriviaParticipant::create([
            'id' => (string) Str::ulid(),
            'room_id' => (string) $room->id,
            'user_id' => (string) $user->id,
            'status' => 'winner',
            'final_rank' => 1,
            'prize_won' => $amount,
            'prize_status' => 'claimed',
        ]);

        return [$participant, $wallet, $user, $room];
    }

    public function test_dispute_marks_participant_as_disputed(): void
    {
        [$participant] = $this->makeClaimedParticipant();

        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado en revisión manual');

        $participant->refresh();
        $this->assertTrue($participant->isDisputed());
        $this->assertSame('disputed', $participant->prize_status);
    }

    public function test_dispute_debits_the_wallet_by_the_prize_amount(): void
    {
        [$participant, $wallet] = $this->makeClaimedParticipant(amount: 50.0, walletBalance: 100.0);

        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado');

        $wallet->refresh();
        $this->assertEqualsWithDelta(50.0, (float) $wallet->balance, 0.001);
        $this->assertSame(0.0, (float) $wallet->pending_debt);
    }

    public function test_dispute_caps_the_debit_at_available_balance_and_registers_the_rest_as_debt(): void
    {
        // chk_wallets_balance_nonneg (CHECK CONSTRAINT real en MySQL) impide
        // que balance quede negativo — decisión de producto: el jugador ya
        // gastó el premio en otra sala/juego, así que el débito se topa al
        // saldo disponible y la diferencia queda en pending_debt para
        // cobrarse del próximo crédito (ver WalletService::settlePendingDebt()).
        [$participant, $wallet] = $this->makeClaimedParticipant(amount: 50.0, walletBalance: 10.0);

        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado');

        $wallet->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $wallet->balance, 0.001);
        $this->assertEqualsWithDelta(40.0, (float) $wallet->pending_debt, 0.001);
    }

    public function test_pending_debt_is_settled_from_the_next_credit(): void
    {
        [$participant, $wallet, $user] = $this->makeClaimedParticipant(amount: 50.0, walletBalance: 10.0);
        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado');

        // balance=0, pending_debt=40 tras la disputa. Un depósito de 100
        // debe descontar la deuda antes de que el usuario vea el saldo nuevo.
        app(\App\Domains\Wallet\Services\WalletService::class)->recordAdminDeposit($user, 100.0, 'Carga de prueba');

        $wallet->refresh();
        $this->assertEqualsWithDelta(60.0, (float) $wallet->balance, 0.001, 'Solo 60 (100 - 40 de deuda) debe acreditarse.');
        $this->assertEqualsWithDelta(0.0, (float) $wallet->pending_debt, 0.001);
    }

    public function test_dispute_records_a_prize_reversal_movement(): void
    {
        [$participant, $wallet] = $this->makeClaimedParticipant(amount: 50.0);

        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado');

        $this->assertDatabaseHas('wallet_movements', [
            'wallet_id' => $wallet->id,
            'type'      => WalletMovement::TYPE_PRIZE_REVERSAL,
            'amount'    => 50.0,
        ]);
    }

    public function test_dispute_throws_when_participant_is_not_claimed(): void
    {
        [$participant] = $this->makeClaimedParticipant();
        $participant->update(['prize_status' => 'waiting']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('estado "claimed"');

        app(GamePrizeService::class)->disputeAndReverse($participant, 'motivo');
    }

    public function test_dispute_is_idempotent_and_never_double_reverses(): void
    {
        [$participant, $wallet] = $this->makeClaimedParticipant(amount: 50.0, walletBalance: 100.0);

        app(GamePrizeService::class)->disputeAndReverse($participant, 'primera disputa');

        // Un segundo intento sobre un premio ya 'disputed' (no 'claimed') debe
        // rechazarse — la FSM ya no permite re-disputar el mismo registro.
        $this->expectException(RuntimeException::class);
        app(GamePrizeService::class)->disputeAndReverse($participant->fresh(), 'segundo intento');

        $wallet->refresh();
        $this->assertEqualsWithDelta(50.0, (float) $wallet->balance, 0.001);
    }

    public function test_retry_failed_prize_payouts_command_never_repays_a_disputed_prize(): void
    {
        // Regresión del bug encontrado al implementar disputeAndReverse():
        // RetryFailedTriviaPrizePayoutsCommand solo excluía isClaimed()/
        // isCancelled() — un premio 'disputed' pasaba esa guarda y el cron
        // (cada 5 min) lo re-acreditaría, deshaciendo la disputa del admin.
        [$participant, $wallet, $user, $room] = $this->makeClaimedParticipant(amount: 50.0, walletBalance: 100.0);

        $game = TriviaGame::create([
            'room_id' => (string) $room->id,
            'game_code' => 'GC' . substr(uniqid(), -6),
            'status' => 'finished',
            'total_questions' => 3,
            'time_per_question' => 20,
            'player_count' => 1,
        ]);

        TriviaScore::create([
            'game_id' => (string) $game->id,
            'player_id' => (string) $user->id,
            'final_score' => 100,
            'correct_answers' => 3,
            'wrong_answers' => 0,
            'position' => 1,
            'total_questions' => 3,
        ]);

        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado');

        $this->artisan('trivia:retry-failed-prize-payouts');

        $wallet->refresh();
        $this->assertEqualsWithDelta(50.0, (float) $wallet->balance, 0.001, 'El premio disputado no debe volver a acreditarse.');
        $this->assertTrue($participant->fresh()->isDisputed());
    }
}
