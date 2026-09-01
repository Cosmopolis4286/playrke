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
use App\Models\Wallet;
use App\Models\WalletMovement;
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
        $this->assertSame('50.00', (string) $wallet->balance);
    }

    public function test_dispute_allows_negative_balance_when_prize_already_spent(): void
    {
        // El jugador ya gastó el premio en otra sala/juego antes de la disputa.
        [$participant, $wallet] = $this->makeClaimedParticipant(amount: 50.0, walletBalance: 10.0);

        app(GamePrizeService::class)->disputeAndReverse($participant, 'Fraude detectado');

        $wallet->refresh();
        $this->assertSame('-40.00', (string) $wallet->balance);
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
        $this->assertSame('50.00', (string) $wallet->balance);
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
        $this->assertSame('50.00', (string) $wallet->balance, 'El premio disputado no debe volver a acreditarse.');
        $this->assertTrue($participant->fresh()->isDisputed());
    }
}
