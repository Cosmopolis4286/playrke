<?php

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Actions\JoinRoomAction;
use App\Domains\Games\Trivia\Actions\StartGameAction;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\GameEngineService;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Trivia de punta a punta (auditoría v1): dos jugadores pagan la entrada con
 * su billetera, juegan la partida, uno acierta y el otro no, y al terminar el
 * premio garantizado se acredita solo al que acertó.
 */
class TriviaFullFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp() : void
    {
        parent::setUp();
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();
        Redis::flushall();
        // AdvanceTriviaQuestion (avance por tiempo) no debe correr solo en la prueba.
        Queue::fake();
    }

    public function test_players_pay_entry_play_and_the_winner_gets_the_prize() : void
    {
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        [$winner, $winnerWallet] = $this->playerWithWallet($currency, 100.0);
        [$loser, $loserWallet] = $this->playerWithWallet($currency, 100.0);

        $room = TriviaRoom::create([
            'code' => 'E2E' . strtoupper(Str::random(4)),
            'title' => 'Trivia E2E',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 2,
            'time_per_question' => 15,
            'entry_fee' => 10,
            'entry_currency_id' => (string) $currency->id,
            'prize_model' => 'guaranteed',
            'prize_amount' => 50,
            'prize_currency_id' => (string) $currency->id,
            'prize_payouts' => [['rank' => 1, 'percent' => 1.0]],
        ]);
        foreach ([1, 2] as $n) {
            TriviaQuestion::create([
                'question_text' => "Pregunta {$n}",
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option_index' => 0,
                'is_active' => true,
            ]);
        }

        // 1. Unirse: se cobra la entrada de cada billetera.
        $join = app(JoinRoomAction::class);
        $join->execute((string) $room->id, (string) $winner->id, 'Ganador');
        $join->execute((string) $room->id, (string) $loser->id, 'Perdedor');
        $this->assertEqualsWithDelta(90.0, (float) $winnerWallet->fresh()->balance, 0.001);
        $this->assertEqualsWithDelta(90.0, (float) $loserWallet->fresh()->balance, 0.001);

        // 2. Iniciar (envía la primera pregunta).
        $state = app(StartGameAction::class)->execute((string) $room->id, null, []);
        $gameId = $state->gameId;

        // 3. Jugar las dos preguntas por HTTP (la ruta real de respuesta,
        //    que es la que escribe trivia_scores): uno acierta y el otro no.
        $this->withoutMiddleware(RoleMiddleware::class);
        $states = app(GameStateRepositoryInterface::class);
        $engine = app(GameEngineService::class);
        for ($q = 1; $q <= 2; $q++) {
            $current = $states->getCurrentQuestion($gameId);
            $this->assertNotNull($current, "Pregunta {$q} no enviada");
            usleep(350_000); // por encima del mínimo humano anti-bot (300 ms)
            foreach ([[$winner, 0], [$loser, 1]] as [$player, $option]) {
                $this->actingAs($player)
                    ->postJson(route('trivia-games.game.answer', $gameId), [
                        'question_id' => (string) $current['question_id'],
                        'selected_option_index' => $option,
                        'response_time_ms' => 400,
                    ])
                    ->assertOk();
            }
            $engine->sendNextQuestion($gameId, (string) $current['question_id']);
        }

        // 4. Sin más preguntas la partida terminó y repartió el premio.
        $this->assertSame('finished', DB::connection('pgsql_trivia')->table('trivia_games')->where('id', $gameId)->value('status'));
        $this->assertEqualsWithDelta(140.0, (float) $winnerWallet->fresh()->balance, 0.001);
        $this->assertEqualsWithDelta(90.0, (float) $loserWallet->fresh()->balance, 0.001);
        $this->assertTrue(WalletMovement::where('wallet_id', $winnerWallet->id)->where('type', WalletMovement::TYPE_PRIZE)->exists());
        $this->assertEqualsWithDelta(50.0, (float) DB::connection('pgsql_trivia')->table('trivia_participants')
            ->where('room_id', (string) $room->id)->where('user_id', (string) $winner->id)->value('prize_won'), 0.001);

        // 5. Efectos secundarios del cierre: ELO de ambos jugadores y XP.
        $this->assertSame(2, DB::connection('pgsql_trivia')->table('trivia_skill_ratings')
            ->whereIn('user_id', [(string) $winner->id, (string) $loser->id])->count());
        $this->assertGreaterThan(0, (int) DB::table('user_levels')->where('user_id', $winner->id)->value('total_xp'));
    }

    /** @return array{0: User, 1: Wallet} */
    private function playerWithWallet(Currency $currency, float $balance) : array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id, 'currency_id' => $currency->id,
            'balance' => $balance, 'bonus_balance' => 0, 'status' => 'ACTIVE',
        ]);

        return [$user, $wallet];
    }
}
