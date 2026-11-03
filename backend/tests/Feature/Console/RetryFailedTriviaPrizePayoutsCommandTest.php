<?php

namespace Tests\Feature\Console;

use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaScore;
use App\Domains\Wallet\Models\Wallet;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\MovementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cubre el escenario que motivó este comando: un ganador de trivia cuyo pago
 * falló (sin wallet activa en el momento del sorteo) queda detectable vía
 * trivia_scores y se reintenta exitosamente una vez la wallet existe —
 * verificado manualmente con Tinker durante el desarrollo, aquí como
 * regresión automatizada.
 */
class RetryFailedTriviaPrizePayoutsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp() : void
    {
        parent::setUp();

        // wallet_movements.type es FK contra movement_types.code (migración
        // 2026_06_27_211954_convert_wallet_movements_type_to_fk) — la BD real
        // tiene el catálogo sembrado manualmente, playrke_testing no. Sin
        // esto, WalletService::recordPrize() falla por constraint FK, no por
        // idempotencia, y el catch de QueryException lo enmascaraba como
        // "ya pagado" antes de corregirlo.
        if (! MovementType::where('code', 'PRIZE')->exists()) {
            MovementType::create([
                'code' => 'PRIZE',
                'label' => 'Premio ganado',
                'is_credit' => true,
                'status' => 'ACTIVE',
            ]);
        }
    }

    #[Test]
    public function command_is_registered_and_runs_successfully() : void
    {
        $this->artisan('trivia:retry-failed-prize-payouts')->assertExitCode(0);
    }

    #[Test]
    public function command_logs_execution_on_success() : void
    {
        $this->artisan('trivia:retry-failed-prize-payouts');

        $this->assertDatabaseHas('command_logs', [
            'command' => 'trivia:retry-failed-prize-payouts',
            'status' => 'SUCCESS',
        ]);
    }

    #[Test]
    public function does_not_pay_winner_with_zero_correct_answers() : void
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TS3', 'name' => 'Test Coin 3', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $room = $this->makeRoom(['prize_currency_id' => (string) $currency->id]);
        $game = $this->makeFinishedGame($room);

        // Posición 1 pero sin ninguna respuesta correcta — el skill gate de
        // PrizeDistributionService debe excluirlo del pago.
        TriviaScore::create([
            'game_id' => (string) $game->id,
            'player_id' => (string) $user->id,
            'final_score' => 0,
            'correct_answers' => 0,
            'wrong_answers' => 3,
            'position' => 1,
            'total_questions' => 3,
        ]);

        TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $user->id,
            'status' => 'playing',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $this->artisan('trivia:retry-failed-prize-payouts')
            ->expectsOutputToContain('acreditados: 0')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'PRIZE',
        ]);
    }

    #[Test]
    public function does_not_retry_already_claimed_participant() : void
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TS2', 'name' => 'Test Coin 2', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $room = $this->makeRoom(['prize_currency_id' => (string) $currency->id]);
        $game = $this->makeFinishedGame($room);

        TriviaScore::create([
            'game_id' => (string) $game->id,
            'player_id' => (string) $user->id,
            'final_score' => 100,
            'correct_answers' => 3,
            'wrong_answers' => 0,
            'position' => 1,
            'total_questions' => 3,
        ]);

        TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $user->id,
            'status' => 'winner',
            'final_rank' => 1,
            'prize_won' => 10,
            'prize_status' => 'claimed',
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $this->artisan('trivia:retry-failed-prize-payouts')
            ->expectsOutputToContain('acreditados: 0')
            ->assertExitCode(0);

        $wallet->refresh();
        $this->assertEquals(0.0, (float) $wallet->balance);
    }

    #[Test]
    public function retries_and_credits_winner_who_never_had_final_rank_set() : void
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TST', 'name' => 'Test Coin', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $room = $this->makeRoom(['prize_currency_id' => (string) $currency->id]);
        $game = $this->makeFinishedGame($room);

        // Ganador registrado en el ranking final (trivia_scores) — esta es la
        // fuente que el comando usa para detectar "quién debía cobrar", ya que
        // sigue existiendo aunque distribute() haya fallado por completo
        // (ej. TriviaRoom::findOrFail() lanzó excepción antes de intentar
        // ningún pago individual, así que ningún TriviaParticipant llegó
        // siquiera a tener final_rank).
        TriviaScore::create([
            'game_id' => (string) $game->id,
            'player_id' => (string) $user->id,
            'final_score' => 100,
            'correct_answers' => 3,
            'wrong_answers' => 0,
            'position' => 1,
            'total_questions' => 3,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        // Participante existe pero sin final_rank/prize_won — distribute()
        // nunca llegó a tocarlo.
        TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $user->id,
            'status' => 'playing',
        ]);

        $this->artisan('trivia:retry-failed-prize-payouts')
            ->expectsOutputToContain('acreditados: 1')
            ->assertExitCode(0);

        // Split por defecto 60/25/15 (ver PrizeDistributionService::normalizedSplit()):
        // con un solo ganador en posición 1, solo cobra el 60% del prize_amount.
        $wallet->refresh();
        $this->assertEquals(6.0, (float) $wallet->balance);

        $participant = TriviaParticipant::where('room_id', $room->id)->where('user_id', $user->id)->first();
        $this->assertEquals('claimed', $participant->prize_status);
    }

    #[Test]
    public function retries_winner_when_final_rank_was_persisted_before_prize_initialization() : void
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TS5', 'name' => 'Test Coin 5', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $room = $this->makeRoom(['prize_currency_id' => (string) $currency->id]);
        $game = $this->makeFinishedGame($room);

        TriviaScore::create([
            'game_id' => (string) $game->id,
            'player_id' => (string) $user->id,
            'final_score' => 100,
            'correct_answers' => 3,
            'wrong_answers' => 0,
            'position' => 1,
            'total_questions' => 3,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        // finishGame() persists final_rank before PrizeDistributionService runs.
        // This reproduces the original T1 failure: final_rank is set, but the
        // prize FSM and amount have not yet been initialized.
        $participant = TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $user->id,
            'status' => 'playing',
            'final_rank' => 1,
            'prize_won' => 0,
            'prize_status' => null,
        ]);

        $this->artisan('trivia:retry-failed-prize-payouts')
            ->expectsOutputToContain('acreditados: 1')
            ->assertExitCode(0);

        $wallet->refresh();
        $this->assertEquals(6.0, (float) $wallet->balance);

        $participant->refresh();
        $this->assertEquals(1, $participant->final_rank);
        $this->assertEquals(6.0, (float) $participant->prize_won);
        $this->assertEquals('claimed', $participant->prize_status);
    }

    #[Test]
    public function suspends_without_auto_retry_when_winner_has_no_active_wallet() : void
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TS4', 'name' => 'Test Coin 4', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $room = $this->makeRoom(['prize_currency_id' => (string) $currency->id]);
        $game = $this->makeFinishedGame($room);

        TriviaScore::create([
            'game_id' => (string) $game->id,
            'player_id' => (string) $user->id,
            'final_score' => 100,
            'correct_answers' => 3,
            'wrong_answers' => 0,
            'position' => 1,
            'total_questions' => 3,
        ]);

        // Sin wallet: el pago debe fallar y quedar 'suspended' para revisión
        // manual — igual que Bingo, un premio suspendido NO se reintenta
        // automáticamente aunque después aparezca una wallet, para no
        // reintentar indefinidamente sin límite (ver GamePrizeService::payout()).
        TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $user->id,
            'status' => 'playing',
        ]);

        $this->artisan('trivia:retry-failed-prize-payouts');

        $participant = TriviaParticipant::where('room_id', $room->id)->where('user_id', $user->id)->first();
        $this->assertEquals('suspended', $participant->prize_status);

        $this->assertDatabaseMissing('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'PRIZE',
        ]);

        // Crear la wallet después no dispara un auto-reintento: sigue suspendido.
        Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $this->artisan('trivia:retry-failed-prize-payouts')
            ->expectsOutputToContain('acreditados: 0');

        $participant->refresh();
        $this->assertEquals('suspended', $participant->prize_status);
    }

    private function makeFinishedGame(TriviaRoom $room) : TriviaGame
    {
        return TriviaGame::create([
            'room_id' => $room->id,
            'game_code' => 'GC' . substr(uniqid(), -6),
            'status' => 'finished',
            'total_questions' => 3,
            'time_per_question' => 20,
            'player_count' => 1,
        ]);
    }

    private function makeRoom(array $overrides = []) : TriviaRoom
    {
        return TriviaRoom::create(array_merge([
            'code' => 'RT' . substr(uniqid(), -6),
            'title' => 'Sala retry premios',
            'host_id' => '1',
            'status' => 'closed',
            'game_mode' => 'free_for_all',
            'max_players' => 10,
            'question_count' => 3,
            'time_per_question' => 20,
            'prize_model' => 'guaranteed',
            'prize_amount' => 10,
        ], $overrides));
    }

    // User::factory() no está disponible (App\Models\User no usa HasFactory
    // en este proyecto) — se crea directamente igual que el resto de fixtures.
    private function makeUser() : User
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
}
