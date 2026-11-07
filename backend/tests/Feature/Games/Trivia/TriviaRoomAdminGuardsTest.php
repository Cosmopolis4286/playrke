<?php

declare(strict_types=1);

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bloqueos de v1 en TriviaRoomAdminController:
 *  – No se puede cerrar/eliminar una sala 'playing' (409): closeRoom() solo
 *    cambia el estado y reembolsa entradas, pero la partida seguiría
 *    corriendo y pagando premios.
 *  – scheduled_at es obligatorio al crear sala: no hay inicio manual
 *    disponible para el admin (StartGameAction exige host = usuario y la
 *    ruta de inicio vive en el grupo role:player).
 */
class TriviaRoomAdminGuardsTest extends TestCase
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
    public function creating_a_room_with_scheduled_at_passes_that_rule() : void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->postJson('/trivia-rooms', [
            'title' => 'Sala con fecha',
            'max_players' => 10,
            'question_count' => 5,
            'time_per_question' => 15,
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ]);

        $response->assertJsonMissingValidationErrors(['scheduled_at']);
    }

    #[Test]
    public function creating_a_room_without_scheduled_at_fails_validation() : void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->postJson('/trivia-rooms', [
            'title' => 'Sala sin fecha',
            'max_players' => 10,
            'question_count' => 5,
            'time_per_question' => 15,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['scheduled_at']);
        $this->assertSame(0, TriviaRoom::where('title', 'Sala sin fecha')->count());
    }

    #[Test]
    public function destroying_a_playing_room_returns_409_and_does_not_refund() : void
    {
        $admin = $this->makeUserWithRole('admin');
        $room = $this->makeRoom('playing');
        $participant = $this->makePaidParticipant($room);

        $response = $this->actingAs($admin)->deleteJson("/trivia-rooms/{$room->id}");

        $response->assertStatus(409);
        $response->assertJson(['error_code' => 'TRIVIA_ROOM_CANNOT_CLOSE_WHILE_PLAYING']);
        $this->assertSame('playing', $room->fresh()->status);
        $this->assertSame('confirmed', $participant->fresh()->status);
        $this->assertDatabaseMissing('wallet_movements', ['user_id' => $participant->user_id]);
    }

    #[Test]
    public function force_closing_a_playing_room_returns_409_and_does_not_refund() : void
    {
        $admin = $this->makeUserWithRole('admin');
        $room = $this->makeRoom('playing');
        $participant = $this->makePaidParticipant($room);

        $response = $this->actingAs($admin)->postJson("/trivia-rooms/{$room->id}/force-close");

        $response->assertStatus(409);
        $response->assertJson(['error_code' => 'TRIVIA_ROOM_CANNOT_CLOSE_WHILE_PLAYING']);
        $this->assertSame('playing', $room->fresh()->status);
        $this->assertSame('confirmed', $participant->fresh()->status);
        $this->assertDatabaseMissing('wallet_movements', ['user_id' => $participant->user_id]);
    }

    #[Test]
    public function play_guard_message_is_translated() : void
    {
        $admin = $this->makeUserWithRole('admin');
        $room = $this->makeRoom('playing');

        $response = $this->actingAs($admin)->deleteJson("/trivia-rooms/{$room->id}");

        $this->assertNotSame('TRIVIA_ROOM_CANNOT_CLOSE_WHILE_PLAYING', $response->json('message'));
        $this->assertNotEmpty($response->json('message'));
    }

    private function makePaidParticipant(TriviaRoom $room) : TriviaParticipant
    {
        return TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => $this->nextId(),
            'status' => 'confirmed',
            'paid_amount' => 5,
            'paid_currency_id' => '1',
        ]);
    }

    private function makeRole(string $name) : Role
    {
        $role = Role::where('name', $name)->where('guard_name', 'web')->first();
        if ($role) {
            return $role;
        }

        $role = new Role([
            'name' => $name,
            'guard_name' => 'web',
            'prefix' => strtoupper($name),
            'type' => 'web',
            'status' => true,
        ]);
        $role->id = $this->nextId();
        $role->save();

        // Ver TriviaAuthorizationControllersTest::makeRole(): guard_name/type
        // no son fillable y se necesitan en memoria para assignRole().
        return $role->refresh();
    }

    private function makeRoom(string $status) : TriviaRoom
    {
        return TriviaRoom::create([
            'code' => 'TG' . substr(uniqid(), -6),
            'title' => 'Sala de prueba de guardas',
            'host_id' => $this->nextId(),
            'status' => $status,
            'max_players' => 10,
            'question_count' => 5,
            'time_per_question' => 15,
        ]);
    }

    private function makeUserWithRole(string $roleName) : User
    {
        $this->makeRole($roleName);
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function nextId() : string
    {
        return (string) (600000000000000000 + (++self::$idCounter));
    }
}
