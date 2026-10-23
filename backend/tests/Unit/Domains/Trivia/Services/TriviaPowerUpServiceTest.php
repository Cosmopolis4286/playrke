<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Games\Trivia\Enums\TriviaPowerUpType;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaPowerUpService;
use App\Models\Games\Trivia\TriviaParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TriviaPowerUpServiceTest extends TestCase
{
    use RefreshDatabase;

    // trivia_rooms/trivia_participants viven en pgsql_trivia — mismo patrón
    // que el resto de tests de este dominio (ver GameEngineServiceTest).
    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    private TriviaPowerUpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resuelto vía el container (no `new`): TriviaPowerUpService ahora
        // depende de AssetConsumptionService (fallback a PlayerAsset cuando
        // se agota el uso gratis por partida — ver
        // MARKETPLACE_ARCHITECTURE_AUDIT.md sección 31).
        $this->service = app(TriviaPowerUpService::class);
        Redis::flushall();
    }

    private function createRoomAndParticipant(string $userId = 'user-1'): array
    {
        $room = TriviaRoom::create([
            'code' => 'PWR001',
            'title' => 'Sala Power-ups',
            'host_id' => '1',
            'status' => 'waiting',
            'max_players' => 10,
            'question_count' => 3,
            'settings' => ['allow_powerups' => true],
        ]);

        $participant = TriviaParticipant::create([
            'id' => (string) Str::ulid(),
            'room_id' => (string) $room->id,
            'user_id' => $userId,
            'status' => 'confirmed',
            'registered_at' => now(),
            'confirmed_at' => now(),
            'power_ups' => TriviaPowerUpType::defaultAllowance(),
        ]);

        return [$room, $participant];
    }

    public function test_consume_decrements_available_uses(): void
    {
        [$room, $participant] = $this->createRoomAndParticipant();

        $this->service->consume((string) $room->id, $participant->user_id, TriviaPowerUpType::FIFTY_FIFTY);

        $participant->refresh();
        $this->assertSame(0, $participant->getAvailablePowerUp(TriviaPowerUpType::FIFTY_FIFTY->value));
        // Los demás tipos no se ven afectados.
        $this->assertSame(1, $participant->getAvailablePowerUp(TriviaPowerUpType::SKIP->value));
    }

    public function test_consume_throws_when_no_uses_left(): void
    {
        [$room, $participant] = $this->createRoomAndParticipant();

        $this->service->consume((string) $room->id, $participant->user_id, TriviaPowerUpType::SKIP);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ya no tienes usos disponibles');

        $this->service->consume((string) $room->id, $participant->user_id, TriviaPowerUpType::SKIP);
    }

    public function test_consume_falls_back_to_inventory_ticket_when_free_use_exhausted(): void
    {
        // player_assets.owner_id es un FK real a users (MySQL) — a
        // diferencia de trivia_participants.user_id (Postgres, columna
        // string libre, ver createRoomAndParticipant()), necesita un User
        // real, no el 'user-1' ficticio que el resto de tests de esta clase
        // usa para trivia_participants.
        $user = \App\Models\User::factory()->create();
        [$room, $participant] = $this->createRoomAndParticipant((string) $user->id);

        $definition = \App\Domains\Marketplace\Inventory\Models\AssetDefinition::firstOrCreate(
            ['code' => 'TRIVIA_HINT_TICKET'],
            [
                'name' => 'Pista de Trivia', 'category' => 'TICKET', 'stackable' => true,
                'tradable' => false, 'transferable' => false, 'consumable' => true,
                'marketplace_enabled' => false, 'game_group' => 'TRIVIA', 'status' => 'ACTIVE',
            ],
        );
        \App\Domains\Marketplace\Inventory\Models\AssetDefinition::forgetCatalogCache();
        \App\Domains\Marketplace\Inventory\Models\PlayerAsset::create([
            'asset_definition_id' => $definition->id, 'owner_id' => $user->id,
            'quantity' => 1, 'status' => 'AVAILABLE', 'acquired_via' => 'ADMIN_GRANT',
        ]);

        // Agota el uso gratis.
        $this->service->consume((string) $room->id, $participant->user_id, TriviaPowerUpType::SKIP);

        // Sin el ticket, esto lanzaría RuntimeException (ver test de arriba) —
        // con el ticket disponible, el fallback lo consume y no lanza.
        $this->service->consume((string) $room->id, $participant->user_id, TriviaPowerUpType::SKIP);

        $this->assertSame(
            'CONSUMED',
            \App\Domains\Marketplace\Inventory\Models\PlayerAsset::where('owner_id', $user->id)
                ->where('asset_definition_id', $definition->id)
                ->value('status'),
        );
    }

    public function test_consume_throws_when_player_not_in_room(): void
    {
        [$room] = $this->createRoomAndParticipant();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No estás inscrito');

        $this->service->consume((string) $room->id, 'unknown-user', TriviaPowerUpType::FIFTY_FIFTY);
    }

    public function test_resolve_fifty_fifty_never_hides_the_correct_answer(): void
    {
        foreach (range(0, 3) as $correctIndex) {
            $hidden = $this->service->resolveFiftyFifty($correctIndex, 4);

            $this->assertCount(2, $hidden);
            $this->assertNotContains($correctIndex, $hidden);
            $this->assertSame(count($hidden), count(array_unique($hidden)));
        }
    }

    public function test_double_points_flag_is_consumed_only_once(): void
    {
        $this->service->activateDoublePoints('game-1', 'user-1', 'question-1');

        $this->assertTrue($this->service->consumeDoublePointsFlag('game-1', 'user-1', 'question-1'));
        // Segunda lectura: ya se consumió, no debe seguir activo.
        $this->assertFalse($this->service->consumeDoublePointsFlag('game-1', 'user-1', 'question-1'));
    }

    public function test_double_points_flag_can_be_checked_without_consuming_it(): void
    {
        $this->service->activateDoublePoints('game-1', 'user-1', 'question-1');

        $this->assertTrue($this->service->hasDoublePointsFlag('game-1', 'user-1', 'question-1'));
        $this->assertTrue($this->service->hasDoublePointsFlag('game-1', 'user-1', 'question-1'));

        $this->service->clearDoublePointsFlag('game-1', 'user-1', 'question-1');

        $this->assertFalse($this->service->hasDoublePointsFlag('game-1', 'user-1', 'question-1'));
    }

    public function test_activation_reservation_is_atomic_and_pending_does_not_grant_effect(): void
    {
        $this->assertTrue($this->service->reserveActivation('game-1', 'user-1', 'question-1', 'double_points'));
        $this->assertFalse($this->service->reserveActivation('game-1', 'user-1', 'question-1', 'double_points'));
        $this->assertFalse($this->service->hasDoublePointsFlag('game-1', 'user-1', 'question-1'));

        $this->service->activateDoublePoints('game-1', 'user-1', 'question-1');
        $this->assertTrue($this->service->hasDoublePointsFlag('game-1', 'user-1', 'question-1'));
    }

    public function test_failed_activation_can_release_reservation_for_retry(): void
    {
        $this->assertTrue($this->service->reserveActivation('game-1', 'user-1', 'question-1', 'extra_time'));
        $this->service->releaseActivation('game-1', 'user-1', 'question-1', 'extra_time');

        $this->assertTrue($this->service->reserveActivation('game-1', 'user-1', 'question-1', 'extra_time'));
        $this->assertSame(0, $this->service->extraTimeBonusMs('game-1', 'user-1', 'question-1'));
    }

    public function test_extra_time_bonus_is_zero_when_not_activated(): void
    {
        $this->assertSame(0, $this->service->extraTimeBonusMs('game-1', 'user-1', 'question-1'));

        $this->service->activateExtraTime('game-1', 'user-1', 'question-1');

        $this->assertSame(
            TriviaPowerUpService::EXTRA_TIME_BONUS_MS,
            $this->service->extraTimeBonusMs('game-1', 'user-1', 'question-1'),
        );
    }
}
