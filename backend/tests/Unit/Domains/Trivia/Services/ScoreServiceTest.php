<?php

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Trivia\DTOs\ScoreDTO;
use App\Domains\Trivia\Services\ScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScoreService();
        Redis::flushall();
    }

    public function test_can_update_player_score(): void
    {
        $score = $this->service->updateScore('game-1', 'player-1', 100);

        $this->assertInstanceOf(ScoreDTO::class, $score);
        $this->assertEquals(100, $score->totalScore);
        $this->assertEquals('player-1', $score->playerId);
    }

    public function test_can_get_player_score(): void
    {
        $this->service->updateScore('game-1', 'player-1', 150);

        $score = $this->service->getPlayerScore('game-1', 'player-1');

        $this->assertEquals(150, $score->totalScore);
    }

    public function test_returns_zero_score_for_unknown_player(): void
    {
        $score = $this->service->getPlayerScore('game-1', 'unknown-player');

        $this->assertEquals(0, $score->totalScore);
    }

    public function test_can_calculate_leaderboard(): void
    {
        $this->service->updateScore('game-1', 'player-1', 300);
        $this->service->updateScore('game-1', 'player-2', 500);
        $this->service->updateScore('game-1', 'player-3', 200);

        $leaderboard = $this->service->getLeaderboard('game-1');

        $this->assertEquals(3, $leaderboard->totalPlayers);
        $this->assertEquals('player-2', $leaderboard->scores[0]->playerId);
        $this->assertEquals(1, $leaderboard->scores[0]->position);
    }

    public function test_can_get_player_position(): void
    {
        $this->service->updateScore('game-1', 'player-1', 100);
        $this->service->updateScore('game-1', 'player-2', 200);
        $this->service->updateScore('game-1', 'player-3', 50);

        $position = $this->service->getPlayerPosition('game-1', 'player-2');

        $this->assertEquals(1, $position);
    }

    public function test_can_reset_scores(): void
    {
        $this->service->updateScore('game-1', 'player-1', 100);

        $this->service->resetScores('game-1');

        $score = $this->service->getPlayerScore('game-1', 'player-1');
        $this->assertEquals(0, $score->totalScore);
    }

    public function test_calculates_time_bonus_correctly(): void
    {
        $points = $this->service->recordCorrectAnswer('game-1', 'player-1', 2000, 100);

        // Respuesta rápida (2s) debería dar bonus
        $this->assertGreaterThan(100, $points);
        $this->assertLessThanOrEqual(200, $points);
    }

    public function test_calculates_low_time_bonus_correctly(): void
    {
        $points = $this->service->recordCorrectAnswer('game-1', 'player-1', 9000, 100);

        // Respuesta lenta (9s) debería dar menos bonus
        $this->assertGreaterThanOrEqual(100, $points);
        $this->assertLessThanOrEqual(150, $points);
    }
}
