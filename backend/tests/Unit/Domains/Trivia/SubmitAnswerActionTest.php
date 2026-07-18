<?php

namespace Tests\Unit\Domains\Trivia;

use App\Domains\Games\Trivia\Actions\SubmitAnswerAction;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\GameEngineService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class SubmitAnswerActionTest extends TestCase
{
    use RefreshDatabase;

    // Igual que GameEngineServiceTest: trivia_rooms/trivia_questions viven en
    // pgsql_trivia y necesitan su propia transacción con rollback.
    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    private SubmitAnswerAction $action;
    private GameEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(SubmitAnswerAction::class);
        $this->engine = app(GameEngineService::class);
        Redis::flushall();

        // Mismo motivo que GameEngineServiceTest: evita que el job de avance
        // automático con delay corra sync y finalice el juego antes de tiempo.
        Queue::fake();

        config(['anticheat.trivia.min_human_response_ms' => 300]);
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

        for ($i = 1; $i <= 3; $i++) {
            TriviaQuestion::create([
                'question_text' => "Pregunta {$i}",
                // Opciones numéricas: QuestionDTO->correctAnswer se arma desde
                // correct_option_index (string), y SubmitAnswerAction exige
                // in_array($answer, $question->options, true) — usar índices
                // como opciones evita el mismatch letra vs índice.
                'options' => ['0', '1', '2', '3'],
                'correct_option_index' => 0,
                'is_active' => true,
            ]);
        }

        return $room;
    }

    public function test_normal_response_passes(): void
    {
        $user = User::factory()->create();
        $room = $this->createRoomWithQuestions();
        $gameState = $this->engine->startGame((string) $room->id, [(string) $user->id]);
        $this->engine->sendNextQuestion($gameState->gameId);

        // No hay forma de retroceder el reloj real, así que probamos con un
        // tiempo de espera real minúsculo — el gate exige >=300ms, usamos
        // usleep para simular una respuesta humana real por encima del umbral.
        usleep(310_000);

        // '0' es la opción correcta: correct_option_index=0.
        $result = $this->action->execute($gameState->gameId, (string) $user->id, '0');

        $this->assertTrue($result->isCorrect);
        $this->assertDatabaseMissing('anti_cheat_flags', [
            'user_id' => (string) $user->id,
            'signal_type' => 'INSTANT_RESPONSE',
        ]);
    }

    public function test_instant_response_is_rejected_and_flagged(): void
    {
        $user = User::factory()->create();
        $room = $this->createRoomWithQuestions();
        $gameState = $this->engine->startGame((string) $room->id, [(string) $user->id]);
        $this->engine->sendNextQuestion($gameState->gameId);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Velocidad de respuesta sospechosa');

        try {
            // Sin usleep: el tiempo transcurrido desde sendNextQuestion() es
            // prácticamente 0ms, muy por debajo del mínimo humano de 300ms.
            $this->action->execute($gameState->gameId, (string) $user->id, '0');
        } finally {
            $this->assertDatabaseHas('anti_cheat_flags', [
                'user_id'     => (string) $user->id,
                'game_slug'   => 'trivia',
                'signal_type' => 'INSTANT_RESPONSE',
            ]);
        }
    }

    public function test_null_client_timestamp_no_longer_bypasses_the_gate(): void
    {
        // Test de regresión directo del bug original: antes, clientTimestamp=null
        // hacía que calculateResponseTime() devolviera 0 y el gate de 300ms se
        // saltara por completo. Ahora el cálculo depende de server_timestamp_ms,
        // no de clientTimestamp — pasar null (como haría un cliente que omite el
        // parámetro) ya no otorga ningún bypass.
        $user = User::factory()->create();
        $room = $this->createRoomWithQuestions();
        $gameState = $this->engine->startGame((string) $room->id, [(string) $user->id]);
        $this->engine->sendNextQuestion($gameState->gameId);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Velocidad de respuesta sospechosa');

        $this->action->execute($gameState->gameId, (string) $user->id, '0', null);
    }

    public function test_missing_server_timestamp_fails_closed_as_time_exceeded(): void
    {
        // Simula un QuestionDTO persistido antes del fix (sin server_timestamp_ms)
        // manipulando directamente el estado en Redis.
        $user = User::factory()->create();
        $room = $this->createRoomWithQuestions();
        $gameState = $this->engine->startGame((string) $room->id, [(string) $user->id]);
        $this->engine->sendNextQuestion($gameState->gameId);

        $question = $this->engine->getCurrentQuestion($gameState->gameId);
        $legacyData = $question->toArray();
        unset($legacyData['server_timestamp_ms']);
        Redis::hset("trivia:game:question:{$gameState->gameId}", 'data', json_encode($legacyData));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tiempo de respuesta excedido');

        $this->action->execute($gameState->gameId, (string) $user->id, '0');
    }

    public function test_clock_drift_creates_flag_without_blocking(): void
    {
        $user = User::factory()->create();
        $room = $this->createRoomWithQuestions();
        $gameState = $this->engine->startGame((string) $room->id, [(string) $user->id]);
        $this->engine->sendNextQuestion($gameState->gameId);

        usleep(310_000);

        // clientTimestamp muy alejado de "ahora" en ms — dispara el drift check
        // sin afectar el cálculo real de responseTimeMs (que usa server_timestamp_ms).
        $driftedClientTimestamp = (microtime(true) * 1000) - 999_999;

        $result = $this->action->execute($gameState->gameId, (string) $user->id, '0', $driftedClientTimestamp);

        $this->assertTrue($result->isCorrect);
        $this->assertDatabaseHas('anti_cheat_flags', [
            'user_id'     => (string) $user->id,
            'signal_type' => 'CLIENT_SERVER_TIME_MISMATCH',
        ]);
    }
}
