<?php

declare(strict_types=1);

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaScore;
use App\Domains\Games\Trivia\Services\GameEngineService;
use App\Domains\Wallet\Models\Wallet;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\MovementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regresión del bug "los premios de trivia nunca se pagan":
 * GameEngineService::finishGame() escribe final_rank en trivia_participants
 * ANTES de llamar a PrizeDistributionService::distribute(), y payoutOne()
 * solo asignaba prize_won si final_rank === null — el premio quedaba en 0 y
 * WalletService::recordPrize() lanzaba por monto no positivo.
 *
 * Recorre el flujo real (startGame → puntaje con respuesta correcta → finishGame) para
 * verificar que el ganador termina con prize_won > 0, prize_status
 * 'claimed' y el movimiento PRIZE en su wallet.
 */
class TriviaFinishGamePrizePayoutTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp() : void
    {
        parent::setUp();

        Redis::flushall();
        // Igual que GameEngineServiceTest: evita que AdvanceTriviaQuestion
        // corra inmediato con QUEUE_CONNECTION=sync y termine la partida solo.
        Queue::fake();

        // wallet_movements.type es FK contra movement_types.code — ver
        // RetryFailedTriviaPrizePayoutsCommandTest::setUp().
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
    public function finish_game_pays_prize_to_winner_whose_final_rank_was_already_set() : void
    {
        $user = $this->makeUser();
        $currency = Currency::create([
            'iso_code' => 'TFG', 'name' => 'Test Finish Coin', 'type' => 'FIAT', 'country_id' => $user->country_id,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 0,
            'status' => 'ACTIVE',
        ]);

        $room = TriviaRoom::create([
            'code' => 'FG' . substr(uniqid(), -6),
            'title' => 'Sala finish premios',
            'host_id' => '1',
            'status' => 'waiting',
            'game_mode' => 'free_for_all',
            'max_players' => 10,
            'question_count' => 1,
            'time_per_question' => 20,
            'prize_model' => 'guaranteed',
            'prize_amount' => 10,
            'prize_currency_id' => (string) $currency->id,
        ]);

        DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
            'id' => (string) Str::random(20),
            'room_id' => (string) $room->id,
            'user_id' => (string) $user->id,
            'status' => 'registered',
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TriviaQuestion::create([
            'question_text' => 'Pregunta única',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option_index' => 0,
            'is_active' => true,
        ]);

        $engine = app(GameEngineService::class);
        $state = $engine->startGame((string) $room->id, [(string) $user->id]);

        // En el gameplay real trivia_scores lo escribe TriviaAnswerController
        // (no GameEngineService::submitAnswer()) — se siembra igual aquí: el
        // jugador respondió correctamente la única pregunta.
        TriviaScore::create([
            'game_id' => $state->gameId,
            'player_id' => (string) $user->id,
            'final_score' => 100,
            'correct_answers' => 1,
            'wrong_answers' => 0,
            'average_response_time_ms' => 1500,
            'total_questions' => 1,
        ]);

        $engine->finishGame($state->gameId);

        $participant = TriviaParticipant::where('room_id', $room->id)
            ->where('user_id', (string) $user->id)
            ->first();

        // Split por defecto 60/25/15: un solo ganador en posición 1 cobra 60% de 10.
        $this->assertSame(1, (int) $participant->final_rank);
        $this->assertEquals(6.0, (float) $participant->prize_won);
        $this->assertSame('winner', $participant->status);
        $this->assertSame('claimed', $participant->prize_status);

        $wallet->refresh();
        $this->assertEquals(6.0, (float) $wallet->balance);

        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'PRIZE',
        ]);
    }

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
