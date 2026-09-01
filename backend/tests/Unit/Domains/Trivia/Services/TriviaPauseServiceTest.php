<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\GameEngineService;
use App\Domains\Games\Trivia\Services\TriviaPauseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class TriviaPauseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    private TriviaPauseService $pauseService;
    private GameEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pauseService = app(TriviaPauseService::class);
        $this->engine = app(GameEngineService::class);
        Redis::flushall();
        Queue::fake();
    }

    private function createRoomWithQuestions(int $thresholdPercent = 0): TriviaRoom
    {
        return TriviaRoom::create([
            'code' => 'PAUSE01',
            'title' => 'Sala Pausa',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 5,
            'settings' => ['pause_disconnect_threshold_percent' => $thresholdPercent],
        ]);
    }

    private function createQuestions(int $count = 5): void
    {
        for ($i = 1; $i <= $count; $i++) {
            TriviaQuestion::create([
                'question_text' => "Pregunta {$i}",
                'options' => ['0', '1', '2', '3'],
                'correct_option_index' => 0,
                'is_active' => true,
            ]);
        }
    }

    public function test_pause_updates_redis_and_postgres_status(): void
    {
        $room = $this->createRoomWithQuestions();
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1']);

        $this->pauseService->pause($gameState->gameId, 'manual');

        $state = app(GameStateRepositoryInterface::class)->getState($gameState->gameId);
        $this->assertSame('paused', $state->status);
        $this->assertSame('manual', $state->metadata['pause_reason']);
        $this->assertSame('paused', TriviaGame::find($gameState->gameId)->status);
    }

    public function test_pause_throws_when_not_active(): void
    {
        $room = $this->createRoomWithQuestions();
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1']);

        $this->pauseService->pause($gameState->gameId, 'manual');

        $this->expectException(RuntimeException::class);
        $this->pauseService->pause($gameState->gameId, 'manual');
    }

    public function test_resume_restores_active_status_and_advances_timestamp(): void
    {
        $room = $this->createRoomWithQuestions();
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1']);
        $this->engine->sendNextQuestion($gameState->gameId);

        $stateRepo = app(GameStateRepositoryInterface::class);
        $questionBefore = $stateRepo->getCurrentQuestion($gameState->gameId);
        $originalTimestamp = (float) $questionBefore['server_timestamp_ms'];

        $this->pauseService->pause($gameState->gameId, 'manual');
        usleep(50_000); // 50ms de "pausa" real para que la duración sea > 0
        $this->pauseService->resume($gameState->gameId);

        $state = $stateRepo->getState($gameState->gameId);
        $this->assertSame('active', $state->status);
        $this->assertSame('playing', TriviaGame::find($gameState->gameId)->status);

        $questionAfter = $stateRepo->getCurrentQuestion($gameState->gameId);
        $this->assertGreaterThan($originalTimestamp, (float) $questionAfter['server_timestamp_ms']);
    }

    public function test_resume_throws_when_not_paused(): void
    {
        $room = $this->createRoomWithQuestions();
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1']);

        $this->expectException(RuntimeException::class);
        $this->pauseService->resume($gameState->gameId);
    }

    public function test_inactive_percent_is_zero_without_enough_question_history(): void
    {
        $room = $this->createRoomWithQuestions();
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1', 'player-2']);

        // Solo 1 pregunta enviada (por startGame) — ventana de 2 preguntas no se cumple.
        $this->assertSame(0.0, $this->pauseService->inactivePlayerPercent($gameState->gameId));
    }

    public function test_inactive_percent_detects_players_with_no_recent_answers(): void
    {
        $room = $this->createRoomWithQuestions();
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1', 'player-2']);

        // 2 preguntas más → 3 preguntas en total, ventana de 2 preguntas cumplida.
        $this->engine->sendNextQuestion($gameState->gameId);
        $this->engine->sendNextQuestion($gameState->gameId);

        // player-1 respondió la pregunta actual; player-2 nunca respondió nada.
        $this->engine->submitAnswer($gameState->gameId, 'player-1', '0', 1000);

        // 1 de 2 jugadores inactivo → 50%.
        $this->assertSame(50.0, $this->pauseService->inactivePlayerPercent($gameState->gameId));
    }

    public function test_maybe_auto_pause_pauses_when_threshold_exceeded(): void
    {
        $room = $this->createRoomWithQuestions(thresholdPercent: 40);
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1', 'player-2']);

        // sendNextQuestion() llama maybeAutoPause() internamente al final —
        // se necesitan 2 llamadas para que used_question_ids alcance la
        // ventana de INACTIVITY_QUESTIONS_WINDOW (2); con 0 respuestas de
        // ambos jugadores tras esas 2 preguntas (100% inactivos > 40%),
        // la segunda llamada debe disparar la pausa automática.
        $this->engine->sendNextQuestion($gameState->gameId);
        $this->engine->sendNextQuestion($gameState->gameId);

        $this->assertSame('paused', TriviaGame::find($gameState->gameId)->status);
    }

    public function test_maybe_auto_pause_does_nothing_when_threshold_is_zero(): void
    {
        $room = $this->createRoomWithQuestions(thresholdPercent: 0);
        $this->createQuestions();
        $gameState = $this->engine->startGame((string) $room->id, ['player-1', 'player-2']);

        $this->engine->sendNextQuestion($gameState->gameId);
        $this->engine->sendNextQuestion($gameState->gameId);

        $this->assertSame('playing', TriviaGame::find($gameState->gameId)->status);
    }
}
