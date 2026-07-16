<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\RoomService;
use App\Events\Games\Trivia\TriviaPlayerJoined;
use App\Events\Games\Trivia\TriviaPlayerLeft;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class RoomServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['mysql', 'pgsql_trivia'];

    protected RoomService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RoomService();
        Event::fake([TriviaPlayerJoined::class, TriviaPlayerLeft::class]);
        Redis::flushall();
    }

    #[Test]
    public function it_creates_room_with_default_values(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'title' => 'Mi Sala de Trivia',
        ]);

        $this->assertInstanceOf(RoomDTO::class, $room);
        $this->assertEquals('Mi Sala de Trivia', $room->title);
        $this->assertEquals('waiting', $room->status);
        $this->assertEquals(100, $room->maxPlayers);
        $this->assertNotNull($room->code);
        $this->assertEquals(6, strlen($room->code));

        // question_count/time_per_question son defaults en BD — RoomDTO no
        // los expone como propiedades propias, se verifican en la fila real.
        // trivia_rooms vive en pgsql_trivia, no en la conexión default.
        $this->assertDatabaseHas('trivia_rooms', [
            'id' => $room->roomId,
            'question_count' => 10,
            'time_per_question' => 15,
        ], 'pgsql_trivia');
    }

    #[Test]
    public function it_creates_room_with_custom_values(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'title' => 'Sala Premium',
            'description' => 'Descripción personalizada',
            'max_players' => 50,
            'question_count' => 20,
            'time_per_question' => 30,
            'code' => 'ABC123',
        ]);

        $this->assertEquals('Sala Premium', $room->title);
        $this->assertEquals(50, $room->maxPlayers);
        $this->assertEquals('ABC123', $room->code);

        $this->assertDatabaseHas('trivia_rooms', [
            'id' => $room->roomId,
            'description' => 'Descripción personalizada',
            'question_count' => 20,
            'time_per_question' => 30,
        ]);
    }

    #[Test]
    public function it_generates_unique_room_code_when_not_provided(): void
    {
        $room1 = $this->service->createRoom(['host_id' => '1']);
        $room2 = $this->service->createRoom(['host_id' => '2']);

        $this->assertNotEquals($room1->code, $room2->code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $room1->code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $room2->code);
    }

    #[Test]
    public function it_stores_room_in_database(): void
    {
        $roomDto = $this->service->createRoom([
            'host_id' => '1',
            'title' => 'Sala Test',
            'code' => 'TEST01',
        ]);

        $this->assertDatabaseHas('trivia_rooms', [
            'id' => $roomDto->roomId,
            'code' => 'TEST01',
            'title' => 'Sala Test',
            'host_id' => '1',
            'status' => 'waiting',
        ]);
    }

    #[Test]
    public function it_caches_room_in_redis(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'title' => 'Sala Redis',
        ]);

        $cached = Redis::hget('trivia:room:' . $room->roomId, 'data');
        $this->assertNotNull($cached);

        $decoded = json_decode($cached, true);
        $this->assertEquals($room->roomId, $decoded['room_id']);
        $this->assertEquals($room->code, $decoded['code']);
    }

    #[Test]
    public function it_retrieves_room_by_id(): void
    {
        $created = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'FINDME',
        ]);

        $retrieved = $this->service->getRoom($created->roomId);

        $this->assertInstanceOf(RoomDTO::class, $retrieved);
        $this->assertEquals($created->roomId, $retrieved->roomId);
        $this->assertEquals('FINDME', $retrieved->code);
    }

    #[Test]
    public function it_retrieves_room_from_redis_cache_on_second_call(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'CACHED',
        ]);

        // Primera llamada — ya viene de Redis porque createRoom() la cachea.
        $this->service->getRoom($room->roomId);

        // Borrar la fila de BD para probar que la segunda lectura viene de
        // Redis, no de una nueva consulta a trivia_rooms.
        Redis::expire('trivia:room:' . $room->roomId, 7200);
        $retrieved = $this->service->getRoom($room->roomId);

        $this->assertEquals($room->roomId, $retrieved->roomId);
    }

    #[Test]
    public function it_throws_exception_when_room_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->getRoom('999999999999999999');
    }

    #[Test]
    public function it_updates_room_configuration(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'title' => 'Original',
            'max_players' => 10,
        ]);

        $updated = $this->service->updateRoomConfig($room->roomId, [
            'title' => 'Actualizado',
            'max_players' => 25,
        ]);

        $this->assertEquals('Actualizado', $updated->title);
        $this->assertEquals(25, $updated->maxPlayers);
    }

    #[Test]
    public function it_rejects_update_when_room_is_not_waiting(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'PLAYING',
        ]);

        TriviaRoom::where('id', $room->roomId)->update(['status' => 'playing']);

        $this->expectException(RuntimeException::class);

        $this->service->updateRoomConfig($room->roomId, ['title' => 'No debería aplicar']);
    }

    #[Test]
    public function it_updates_room_cache_after_update(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'UPDATE',
        ]);

        $this->service->updateRoomConfig($room->roomId, ['title' => 'Nuevo Título']);

        $cached = Redis::hget('trivia:room:' . $room->roomId, 'data');
        $decoded = json_decode($cached, true);
        $this->assertEquals('Nuevo Título', $decoded['title']);
    }

    #[Test]
    public function it_closes_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'CLOSE',
        ]);

        $this->service->closeRoom($room->roomId, 'test');

        $this->assertDatabaseHas('trivia_rooms', [
            'id' => $room->roomId,
            'status' => 'closed',
        ]);
        $this->assertNull(Redis::hget('trivia:room:' . $room->roomId, 'data'));
    }

    #[Test]
    public function it_allows_player_to_join_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'JOIN01',
            'max_players' => 10,
        ]);

        $player = $this->service->joinRoom($room->roomId, 'player_456', ['nickname' => 'Jugador Test']);

        $this->assertInstanceOf(PlayerDTO::class, $player);
        $this->assertEquals('player_456', $player->playerId);
        $this->assertEquals('Jugador Test', $player->nickname);
        $this->assertEquals($room->roomId, $player->roomId);

        Event::assertDispatched(TriviaPlayerJoined::class, function ($event) use ($room, $player) {
            return $event->roomId === $room->roomId && $event->playerId === $player->playerId;
        });
    }

    #[Test]
    public function it_increments_current_players_count_on_join(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'COUNT',
        ]);

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);

        $updated = TriviaRoom::find($room->roomId);
        $this->assertEquals(1, $updated->current_players);
    }

    #[Test]
    public function it_throws_exception_when_joining_full_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'max_players' => 2,
            'code' => 'FULL',
        ]);

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);
        $this->service->joinRoom($room->roomId, 'p2', ['nickname' => 'Player 2']);

        $this->expectException(RuntimeException::class);

        $this->service->joinRoom($room->roomId, 'p3', ['nickname' => 'Player 3']);
    }

    #[Test]
    public function it_throws_exception_when_joining_non_waiting_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'PLAYING2',
        ]);

        TriviaRoom::where('id', $room->roomId)->update(['status' => 'playing']);
        // Refrescar cache de Redis con el nuevo status — getRoom() dentro de
        // joinRoom() prioriza Redis, que sigue con status 'waiting' si no se
        // actualiza aquí.
        Redis::del('trivia:room:' . $room->roomId);

        $this->expectException(RuntimeException::class);

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);
    }

    #[Test]
    public function it_returns_existing_player_without_error_on_duplicate_join(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'DUP',
        ]);

        $first = $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);
        $second = $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1 Updated']);

        $this->assertEquals($first->playerId, $second->playerId);

        // No debe duplicarse en el contador de jugadores.
        $this->assertEquals(1, TriviaRoom::find($room->roomId)->current_players);
    }

    #[Test]
    public function it_allows_player_to_leave_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'LEAVE',
        ]);

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);
        $this->service->leaveRoom($room->roomId, 'p1');

        Event::assertDispatched(TriviaPlayerLeft::class, function ($event) use ($room) {
            return $event->roomId === $room->roomId && $event->playerId === 'p1';
        });
    }

    #[Test]
    public function it_decrements_current_players_count_on_leave(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'DEC',
        ]);

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);
        $this->service->joinRoom($room->roomId, 'p2', ['nickname' => 'Player 2']);
        $this->assertEquals(2, TriviaRoom::find($room->roomId)->current_players);

        $this->service->leaveRoom($room->roomId, 'p1');

        $this->assertEquals(1, TriviaRoom::find($room->roomId)->current_players);
    }

    #[Test]
    public function it_checks_player_presence_in_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'CHECK',
        ]);

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);

        $this->assertTrue($this->service->isPlayerInRoom($room->roomId, 'p1'));
        $this->assertFalse($this->service->isPlayerInRoom($room->roomId, 'p2'));
    }

    #[Test]
    public function it_reports_room_as_full(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'max_players' => 1,
            'code' => 'ISFULL',
        ]);

        $this->assertFalse($this->service->isRoomFull($room->roomId));

        $this->service->joinRoom($room->roomId, 'p1', ['nickname' => 'Player 1']);

        $this->assertTrue($this->service->isRoomFull($room->roomId));
    }

    #[Test]
    public function it_sets_room_ttl_in_redis(): void
    {
        $room = $this->service->createRoom([
            'host_id' => '1',
            'code' => 'TTL',
        ]);

        $ttl = Redis::ttl('trivia:room:' . $room->roomId);

        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(7200, $ttl); // 2 horas
    }
}
