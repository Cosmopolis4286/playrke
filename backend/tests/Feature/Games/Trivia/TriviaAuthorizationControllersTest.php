<?php

declare(strict_types=1);

namespace Tests\Feature\Games\Trivia;

use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cierra el hueco de defensa en profundidad de Frente 1 en el dominio
 * Trivia: TriviaRoomAdminController, TriviaQuestionController y
 * TriviaPrizeDisputeController no llamaban authorize() en ningún método
 * mutante — solo estaban protegidos por el middleware `role:admin,
 * superadmin` de ruta. Este test confirma que las Policies nuevas
 * (TriviaRoomPolicy extendida, TriviaQuestionPolicy, TriviaPrizeDisputePolicy)
 * no rompen el acceso legítimo de admin/superadmin (falso-positivo 403
 * sería la peor regresión posible aquí).
 *
 * IMPORTANTE — a diferencia de Subsidiary, estos 3 controllers NO usan
 * implicit route model binding (reciben `string $id` y llaman
 * TriviaX::findOrFail($id) manualmente) — por lo tanto NO pasan por
 * HasHashedRouteKey::resolveRouteBinding(), y las URLs deben construirse
 * con el id crudo (->id), no con ->getRouteKey(). Confirmado leyendo el
 * frontend real (QuestionsIndex.vue, RoomsIndex.vue, RoomsShow.vue): todos
 * usan `row.id` tal cual en las rutas.
 *
 * El rol 'player' aquí queda bloqueado por el middleware `role:admin,
 * superadmin` de ruta ANTES de llegar al controller/authorize() — para
 * peticiones no-JSON, RoleMiddleware::forbiddenResponse() redirige (302)
 * en vez de devolver 403 (ver RoleMiddleware.php). El authorize() nuevo es
 * una segunda capa que nunca llega a evaluarse en este caso — lo que este
 * test verifica para 'player' es que la mutación NO ocurre, sea cual sea
 * la capa que la bloqueó.
 */
class TriviaAuthorizationControllersTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    private static int $idCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    private function nextId(): string
    {
        return (string) (500000000000000000 + (++self::$idCounter));
    }

    private function makeRole(string $name): Role
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

        return $role;
    }

    private function makeUserWithRole(string $roleName): User
    {
        $this->makeRole($roleName);
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function makeRoom(): TriviaRoom
    {
        return TriviaRoom::create([
            'code' => 'TA' . substr(uniqid(), -6),
            'title' => 'Sala de prueba de autorización',
            'host_id' => (string) $this->nextId(),
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 5,
            'time_per_question' => 15,
        ]);
    }

    private function makeQuestion(): TriviaQuestion
    {
        return TriviaQuestion::create([
            'question_text' => '¿Pregunta de prueba?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option_index' => 0,
            'is_active' => true,
        ]);
    }

    // ---------------------------------------------------------------
    // TriviaRoomAdminController
    // ---------------------------------------------------------------

    #[Test]
    public function player_cannot_destroy_a_trivia_room(): void
    {
        $player = $this->makeUserWithRole('player');
        $room = $this->makeRoom();

        $response = $this->actingAs($player)
            ->delete("/trivia-rooms/{$room->id}");

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertSame('waiting', $room->fresh()->status);
    }

    #[Test]
    public function admin_can_destroy_a_trivia_room(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $room = $this->makeRoom();

        $response = $this->actingAs($admin)
            ->delete("/trivia-rooms/{$room->id}");

        // No debe ser 403 (el guardrail de defensa en profundidad no debe
        // bloquear al rol que sí tiene acceso legítimo).
        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertSame('closed', $room->fresh()->status);
    }

    #[Test]
    public function player_cannot_force_close_a_trivia_room(): void
    {
        $player = $this->makeUserWithRole('player');
        $room = $this->makeRoom();
        $room->update(['status' => 'playing']);

        $response = $this->actingAs($player)
            ->post("/trivia-rooms/{$room->id}/force-close");

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertSame('playing', $room->fresh()->status);
    }

    #[Test]
    public function superadmin_can_force_close_a_trivia_room(): void
    {
        $superadmin = $this->makeUserWithRole('superadmin');
        $room = $this->makeRoom();
        $room->update(['status' => 'playing']);

        $response = $this->actingAs($superadmin)
            ->post("/trivia-rooms/{$room->id}/force-close");

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertSame('closed', $room->fresh()->status);
    }

    // ---------------------------------------------------------------
    // TriviaQuestionController
    // ---------------------------------------------------------------

    #[Test]
    public function player_cannot_delete_a_trivia_question(): void
    {
        $player = $this->makeUserWithRole('player');
        $question = $this->makeQuestion();

        $response = $this->actingAs($player)
            ->delete("/trivia-questions/room/all/{$question->id}");

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertNotNull(TriviaQuestion::find($question->id));
    }

    #[Test]
    public function admin_can_delete_a_trivia_question(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $question = $this->makeQuestion();

        $response = $this->actingAs($admin)
            ->delete("/trivia-questions/room/all/{$question->id}");

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertStatus(302);
        $response->assertSessionMissing('error');
    }

    #[Test]
    public function player_cannot_toggle_active_a_trivia_question(): void
    {
        $player = $this->makeUserWithRole('player');
        $question = $this->makeQuestion();

        $response = $this->actingAs($player)
            ->patch("/trivia-questions/room/all/{$question->id}/toggle-active");

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertTrue($question->fresh()->is_active);
    }

    #[Test]
    public function admin_can_toggle_active_a_trivia_question(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $question = $this->makeQuestion();

        $response = $this->actingAs($admin)
            ->patch("/trivia-questions/room/all/{$question->id}/toggle-active");

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertStatus(302);
        $this->assertFalse($question->fresh()->is_active);
    }

    // ---------------------------------------------------------------
    // TriviaPrizeDisputeController
    // ---------------------------------------------------------------

    #[Test]
    public function player_cannot_dispute_a_trivia_prize(): void
    {
        $player = $this->makeUserWithRole('player');
        $room = $this->makeRoom();
        $participant = TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $this->nextId(),
            'status' => 'winner',
            'prize_status' => 'claimed',
        ]);

        $response = $this->actingAs($player)
            ->post("/trivia-rooms/{$room->id}/participants/{$participant->id}/dispute", [
                'reason' => 'Sospecha de colusión entre jugadores de la sala.',
            ]);

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertSame('claimed', $participant->fresh()->prize_status);
    }

    #[Test]
    public function admin_disputing_a_non_claimed_prize_gets_a_business_error_not_403(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $room = $this->makeRoom();
        // prize_status distinto de 'claimed' fuerza el RuntimeException de
        // GamePrizeService::disputeAndReverse() — lo que importa aquí es
        // que NO sea un 403 (la autorización sí debe dejarlo pasar).
        $participant = TriviaParticipant::create([
            'room_id' => $room->id,
            'user_id' => (string) $this->nextId(),
            'status' => 'winner',
            'prize_status' => 'waiting',
        ]);

        $response = $this->actingAs($admin)
            ->post("/trivia-rooms/{$room->id}/participants/{$participant->id}/dispute", [
                'reason' => 'Sospecha de colusión entre jugadores de la sala.',
            ]);

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }
}
