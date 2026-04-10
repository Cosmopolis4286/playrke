<?php

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Trivia\DTOs\GameStateDTO;
use App\Domains\Trivia\Services\GameEngineService;
use App\Domains\Trivia\Services\RoomService;
use App\Domains\Trivia\Services\ScoreService;
use App\Domains\Trivia\Repositories\RedisGameStateRepository;
use App\Domains\Trivia\Models\TriviaRoom;
use App\Domains\Trivia\Models\TriviaQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class GameEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $stateRepo = new RedisGameStateRepository();
        $scoreService = new ScoreService();
        $roomService = new RoomService();
        
        $this->service = new GameEngineService($stateRepo, $scoreService, $roomService);
        Redis::flushall();
    }

    private function createRoomWithQuestions(): TriviaRoom
    {
        $room = TriviaRoom::create([
            'code' => 'TEST01',
            'title' => 'Sala Test',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 3,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            TriviaQuestion::create([
                'room_id' => $room->id,
                'question_number' => $i,
                'question_text' => "Pregunta {$i}",
                'correct_answer' => 'A',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
            ]);
        }

        return $room;
    }

    public function test_can_start_game(): void
    {
        $room = $this->createRoomWithQuestions();
        $playerIds = ['player-1', 'player-2'];

        $gameState = $this->service->startGame((string) $room->id, $playerIds);

        $this->assertInstanceOf(GameStateDTO::class, $gameState);
        $this->assertEquals('active', $gameState->status);
        $this->assertEquals(3, $gameState->totalQuestions);
        $this->assertCount(2, $gameState->playerIds);
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

        $result = $this->service->submitAnswer(
            $gameState->gameId,
            'player-1',
            'A',
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
            'B',
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
}
