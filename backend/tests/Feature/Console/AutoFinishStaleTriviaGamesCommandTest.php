<?php

namespace Tests\Feature\Console;

use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\GameEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutoFinishStaleTriviaGamesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp() : void
    {
        parent::setUp();
        Redis::flushall();
        Queue::fake();
    }

    #[Test]
    public function command_is_registered_and_runs_successfully() : void
    {
        $this->artisan('trivia:auto-finish-stale-games')->assertExitCode(0);
    }

    #[Test]
    public function command_logs_execution_on_success() : void
    {
        $this->artisan('trivia:auto-finish-stale-games');

        $this->assertDatabaseHas('command_logs', [
            'command' => 'trivia:auto-finish-stale-games',
            'status' => 'SUCCESS',
        ]);
    }

    #[Test]
    public function does_not_touch_games_already_finished() : void
    {
        [, $gameId] = $this->createActiveGame();

        TriviaGame::where('id', $gameId)->update(['status' => 'finished']);

        $this->artisan('trivia:auto-finish-stale-games')
            ->expectsOutputToContain('0 finalizados')
            ->assertExitCode(0);
    }

    #[Test]
    public function finishes_game_stale_for_more_than_five_minutes() : void
    {
        [, $gameId] = $this->createActiveGame();

        // Retrocede la marca de last_activity que GameEngineService::startGame()
        // fija en el momento de crear el estado — simula 6 minutos de inactividad
        // sin depender de dormir el proceso o mockear time().
        $key = 'trivia:game:' . $gameId;
        $data = json_decode(Redis::hget($key, 'data'), true);
        $data['metadata']['last_activity'] = time() - 360;
        Redis::hset($key, 'data', json_encode($data));

        $this->artisan('trivia:auto-finish-stale-games')
            ->expectsOutputToContain('finalizados')
            ->assertExitCode(0);

        $game = TriviaGame::find($gameId);
        $this->assertEquals('finished', $game->status);
    }

    #[Test]
    public function leaves_active_game_untouched_when_recently_active() : void
    {
        [, $gameId] = $this->createActiveGame();

        $this->artisan('trivia:auto-finish-stale-games');

        $game = TriviaGame::find($gameId);
        $this->assertEquals('playing', $game->status);
    }

    private function createActiveGame() : array
    {
        $room = TriviaRoom::create([
            'code' => 'AF' . substr(uniqid(), -6),
            'title' => 'Sala auto-finish',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 3,
        ]);

        TriviaQuestion::create([
            'question_text' => 'Pregunta 1',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option_index' => 0,
            'is_active' => true,
        ]);

        $gameState = app(GameEngineService::class)->startGame((string) $room->id, ['player-1']);

        return [$room, $gameState->gameId];
    }
}
