<?php

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * T4: el jugador inscrito llega a la partida aunque se pierda el evento en
 * tiempo real de inicio (polling a /state) o entre tarde (redirección).
 */
class TriviaWaitingRoomStatusTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected function setUp() : void
    {
        parent::setUp();
        Redis::flushall();
    }

    #[Test]
    public function a_participant_arriving_late_is_sent_to_the_game() : void
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom('playing');
        $this->registerParticipant($room, $player);
        $game = $this->makeGame($room);

        $this->actingAs($player)->get("/trivia/room/{$room->code}/lobby")
            ->assertRedirect('/trivia/play/' . $game->id);
    }

    #[Test]
    public function state_gives_the_running_game_only_to_participants() : void
    {
        $player = $this->makePlayer();
        $outsider = $this->makePlayer();
        $room = $this->makeRoom('playing', scheduledAt: now()->subMinute());
        $this->registerParticipant($room, $player);
        $game = $this->makeGame($room);

        $this->actingAs($player)->getJson("/trivia/room/{$room->code}/state")
            ->assertOk()
            ->assertJsonPath('status', 'playing')
            ->assertJsonPath('gameId', (string) $game->id)
            ->assertJsonPath('players.0.id', (string) $player->id);

        $this->actingAs($outsider)->getJson("/trivia/room/{$room->code}/state")
            ->assertOk()
            ->assertJsonPath('gameId', null);
    }

    #[Test]
    public function state_includes_the_scheduled_start() : void
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom('waiting', scheduledAt: now()->addHour());

        $response = $this->actingAs($player)->getJson("/trivia/room/{$room->code}/state")->assertOk();

        $this->assertNotNull($response->json('scheduledAt'));
        $this->assertNull($response->json('gameId'));
    }

    #[Test]
    public function state_reports_a_cancelled_room_instead_of_404() : void
    {
        $player = $this->makePlayer();
        $room = $this->makeRoom('cancelled');
        $this->registerParticipant($room, $player);

        $this->actingAs($player)->getJson("/trivia/room/{$room->code}/state")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('gameId', null);
    }

    private function makeGame(TriviaRoom $room) : TriviaGame
    {
        return TriviaGame::create([
            'room_id' => (string) $room->id,
            'game_code' => strtoupper(Str::random(6)),
            'status' => 'playing',
            'total_questions' => 5,
            'time_per_question' => 15,
            'player_count' => 1,
            'started_at' => now(),
        ]);
    }

    private function makePlayer() : User
    {
        $role = Role::where('name', 'player')->where('guard_name', 'web')->first();
        if (! $role) {
            $role = new Role(['name' => 'player', 'guard_name' => 'web', 'prefix' => 'PLAYER', 'type' => 'web', 'status' => true]);
            $role->id = $this->nextId();
            $role->save();
            $role->refresh();
        }

        $user = User::factory()->create();
        $user->assignRole('player');

        return $user;
    }

    private function makeRoom(string $status, $scheduledAt = null) : TriviaRoom
    {
        return TriviaRoom::create([
            'code' => 'WR' . strtoupper(Str::random(5)),
            'title' => 'Sala espera',
            'host_id' => $this->nextId(),
            'status' => $status,
            'max_players' => 10,
            'question_count' => 5,
            'time_per_question' => 15,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    private function nextId() : string
    {
        return (string) (510000000000000000 + (++self::$idCounter));
    }

    private function registerParticipant(TriviaRoom $room, User $user) : void
    {
        DB::connection('pgsql_trivia')->table('trivia_participants')->insert([
            'id' => (string) Str::random(20),
            'room_id' => (string) $room->id,
            'user_id' => (string) $user->id,
            'status' => 'confirmed',
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
