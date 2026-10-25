<?php

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use App\Domains\Games\Trivia\Services\GameEngineService;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GameEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabase por defecto solo migra/envuelve la conexión default
    // (mysql). trivia_rooms/trivia_participants viven en pgsql_trivia
    // (apunta a trivia_testing — ver phpunit.xml) y necesitan su propia
    // transacción con rollback para no ensuciar esa BD entre tests.
    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    private GameEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Resuelto vía el contenedor: GameEngineService depende de
        // PrizeDistributionService (→ GamePrizeService → WalletService, con
        // su propio árbol de dependencias) además de ScoreService y las demás
        // — más robusto ante cambios futuros de constructor que armarlas a mano.
        $this->service = app(GameEngineService::class);
        Redis::flushall();

        // sendNextQuestion() despacha AdvanceTriviaQuestion con delay como job
        // de seguridad (avanza si nadie responde a tiempo). Con QUEUE_CONNECTION
        // =sync ese delay se ignora y el job corre inmediato, finalizando el
        // juego antes de que el test alcance a llamar submitAnswer(). Queue::fake()
        // deja el job encolado sin ejecutarlo.
        Queue::fake();
    }

    private function createRoomWithQuestions(): TriviaRoom
    {
        $room = TriviaRoom::create([
            'code' => 'TEST01',
            'title' => 'Sala Test',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 3,
        ]);

        // The engine takes its authoritative player snapshot under the room lock.
        foreach (['player-1', 'player-2'] as $playerId) {
            DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
                'id' => (string) Str::random(20),
                'room_id' => (string) $room->id,
                'user_id' => $playerId,
                'status' => 'registered',
                'registered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            TriviaQuestion::create([
                'question_text' => "Pregunta {$i}",
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option_index' => 0,
                'is_active' => true,
            ]);
        }

        return $room;
    }

    public function test_can_start_game(): void
    {
        $room = $this->createRoomWithQuestions();
        // Deliberately stale caller snapshot: the engine must read the locked DB snapshot.
        $playerIds = ['stale-player'];

        $gameState = $this->service->startGame((string) $room->id, $playerIds);

        $this->assertInstanceOf(GameStateDTO::class, $gameState);
        $this->assertEquals('active', $gameState->status);
        $this->assertEquals(3, $gameState->totalQuestions);
        $this->assertEqualsCanonicalizing(['player-1', 'player-2'], $gameState->playerIds);
    }

    public function test_game_is_active_after_start(): void
    {
        $room = $this->createRoomWithQuestions();
        $gameState = $this->service->startGame((string) $room->id, ['player-1']);

        $this->assertTrue($this->service->isGameActive($gameState->gameId));
    }

    public function test_can_send_next_question(): void
    {
        $room = $this->createRoomWithQuestions();
        $gameState = $this->service->startGame((string) $room->id, ['player-1']);

        $question = $this->service->sendNextQuestion($gameState->gameId);

        $this->assertNotNull($question);
        $this->assertEquals(1, $question->questionNumber);
    }

    public function test_can_submit_answer(): void
    {
        $room = $this->createRoomWithQuestions();
        $gameState = $this->service->startGame((string) $room->id, ['player-1']);
        $this->service->sendNextQuestion($gameState->gameId);

        // correct_option_index = 0 en createRoomWithQuestions() → la respuesta
        // correcta se representa como el índice "0" (string), no como letra.
        $result = $this->service->submitAnswer(
            $gameState->gameId,
            'player-1',
            '0',
            2000
        );

        $this->assertTrue($result->isCorrect);
        $this->assertGreaterThan(0, $result->pointsEarned);
    }

    public function test_wrong_answer_gets_zero_points(): void
    {
        $room = $this->createRoomWithQuestions();
        $gameState = $this->service->startGame((string) $room->id, ['player-1']);
        $this->service->sendNextQuestion($gameState->gameId);

        $result = $this->service->submitAnswer(
            $gameState->gameId,
            'player-1',
            '1',
            2000
        );

        $this->assertFalse($result->isCorrect);
        $this->assertEquals(0, $result->pointsEarned);
    }

    public function test_cannot_start_game_in_non_waiting_room(): void
    {
        $room = $this->createRoomWithQuestions();
        $room->update(['status' => 'playing']);

        $this->expectException(\RuntimeException::class);
        $this->service->startGame((string) $room->id, ['player-1']);
    }
    public function test_cannot_start_game_while_room_is_being_cancelled(): void
    {
        $room = $this->createRoomWithQuestions();
        $room->update(['status' => 'cancelling']);

        // The cancellation command claims the same PostgreSQL row under
        // lockForUpdate(); the engine must reject a start after that claim.
        $this->expectException(\\RuntimeException::class);
        $this->service->startGame((string) $room->id, ['player-1']);
    }

}
