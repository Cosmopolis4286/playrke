<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Trivia\Services;

use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\Events\PlayerJoined;
use App\Domains\Games\Trivia\Events\PlayerLeft;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\RoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class RoomServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RoomService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RoomService();
        Event::fake([PlayerJoined::class, PlayerLeft::class]);
        Redis::flushAll();
    }

    #[Test]
    public function it_creates_room_with_default_values(): void
    {
        $config = [
            'host_id' => 'user_123',
            'title' => 'Mi Sala de Trivia',
        ];

        $room = $this->service->createRoom($config);

        $this->assertInstanceOf(RoomDTO::class, $room);
        $this->assertEquals('Mi Sala de Trivia', $room->title);
        $this->assertEquals('waiting', $room->status);
        $this->assertEquals(100, $room->maxPlayers);
        $this->assertEquals(10, $room->questionCount);
        $this->assertEquals(15, $room->timePerQuestion);
        $this->assertNotNull($room->code);
        $this->assertEquals(6, strlen($room->code));
    }

    #[Test]
    public function it_creates_room_with_custom_values(): void
    {
        $config = [
            'host_id' => 'user_123',
            'title' => 'Sala Premium',
            'description' => 'Descripción personalizada',
            'max_players' => 50,
            'question_count' => 20,
            'time_per_question' => 30,
            'code' => 'ABC123',
        ];

        $room = $this->service->createRoom($config);

        $this->assertEquals('Sala Premium', $room->title);
        $this->assertEquals('Descripción personalizada', $room->description);
        $this->assertEquals(50, $room->maxPlayers);
        $this->assertEquals(20, $room->questionCount);
        $this->assertEquals(30, $room->timePerQuestion);
        $this->assertEquals('ABC123', $room->code);
    }

    #[Test]
    public function it_generates_unique_room_code_when_not_provided(): void
    {
        $room1 = $this->service->createRoom(['host_id' => 'user_1']);
        $room2 = $this->createRoom(['host_id' => 'user_2']);

        $this->assertNotEquals($room1->code, $room2->code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $room1->code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $room2->code);
    }

    #[Test]
    public function it_stores_room_in_database(): void
    {
        $config = [
            'host_id' => 'user_123',
            'title' => 'Sala Test',
            'code' => 'TEST01',
        ];

        $roomDto = $this->service->createRoom($config);

        $this->assertDatabaseHas('trivia_rooms', [
            'id' => $roomDto->id,
            'code' => 'TEST01',
            'title' => 'Sala Test',
            'host_id' => 'user_123',
            'status' => 'waiting',
        ]);
    }

    #[Test]
    public function it_caches_room_in_redis(): void
    {
        $config = [
            'host_id' => 'user_123',
            'title' => 'Sala Redis',
        ];

        $room = $this->service->createRoom($config);

        $cached = Redis::get('trivia:room:' . $room->code);
        $this->assertNotNull($cached);
        
        $decoded = json_decode($cached, true);
        $this->assertEquals($room->id, $decoded['id']);
        $this->assertEquals($room->code, $decoded['code']);
    }

    #[Test]
    public function it_retrieves_room_by_code(): void
    {
        $created = $this->service->createRoom([
            'host_id' => 'user_123',
            'code' => 'FINDME',
        ]);

        $retrieved = $this->service->getRoom('FINDME');

        $this->assertInstanceOf(RoomDTO::class, $retrieved);
        $this->assertEquals($created->id, $retrieved->id);
        $this->assertEquals('FINDME', $retrieved->code);
    }

    #[Test]
    public function it_retrieves_room_from_redis_cache(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'user_123',
            'code' => 'CACHED',
        ]);

        // Primera llamada - cache miss
        $this->service->getRoom('CACHED');
        
        // Segunda llamada - cache hit
        $retrieved = $this->service->getRoom('CACHED');
        
        $this->assertEquals($room->id, $retrieved->id);
    }

    #[Test]
    public function it_throws_exception_when_room_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Room not found');

        $this->service->getRoom('INVALID');
    }

    #[Test]
    public function it_updates_room_configuration(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'user_123',
            'title' => 'Original',
            'max_players' => 10,
        ]);

        $updated = $this->service->updateRoom($room->id, [
            'title' => 'Actualizado',
            'max_players' => 25,
            'status' => 'playing',
        ]);

        $this->assertEquals('Actualizado', $updated->title);
        $this->assertEquals(25, $updated->maxPlayers);
        $this->assertEquals('playing', $updated->status);
    }

    #[Test]
    public function it_updates_room_cache_after_update(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'user_123',
            'code' => 'UPDATE',
        ]);

        $this->service->updateRoom($room->id, ['title' => 'Nuevo Título']);

        $cached = Redis::get('trivia:room:UPDATE');
        $decoded = json_decode($cached, true);
        $this->assertEquals('Nuevo Título', $decoded['title']);
    }

    #[Test]
    public function it_deletes_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'user_123',
            'code' => 'DELETE',
        ]);

        $this->service->deleteRoom($room->id);

        $this->assertDatabaseMissing('trivia_rooms', ['id' => $room->id]);
        $this->assertNull(Redis::get('trivia:room:DELETE'));
    }

    #[Test]
    public function it_allows_player_to_join_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'JOIN01',
            'max_players' => 10,
        ]);

        $player = $this->service->joinRoom($room->code, 'player_456', 'Jugador Test');

        $this->assertInstanceOf(PlayerDTO::class, $player);
        $this->assertEquals('player_456', $player->id);
        $this->assertEquals('Jugador Test', $player->name);
        $this->assertEquals($room->code, $player->roomCode);
        
        Event::assertDispatched(PlayerJoined::class, function ($event) use ($room, $player) {
            return $event->roomCode === $room->code && $event->playerId === $player->id;
        });
    }

    #[Test]
    public function it_increments_current_players_count_on_join(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'COUNT',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        
        $updated = TriviaRoom::find($room->id);
        $this->assertEquals(1, $updated->current_players);
    }

    #[Test]
    public function it_stores_player_in_redis_on_join(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'STORE',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');

        $players = Redis::hgetall('trivia:room:STORE:players');
        $this->assertArrayHasKey('p1', $players);
        
        $playerData = json_decode($players['p1'], true);
        $this->assertEquals('Player 1', $playerData['name']);
    }

    #[Test]
    public function it_throws_exception_when_joining_full_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'max_players' => 2,
            'code' => 'FULL',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        $this->service->joinRoom($room->code, 'p2', 'Player 2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Room is full');

        $this->service->joinRoom($room->code, 'p3', 'Player 3');
    }

    #[Test]
    public function it_throws_exception_when_joining_playing_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'PLAYING',
        ]);
        
        $this->service->updateRoom($room->id, ['status' => 'playing']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Game already started');

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
    }

    #[Test]
    public function it_allows_player_to_leave_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'LEAVE',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        $this->service->leaveRoom($room->code, 'p1');

        Event::assertDispatched(PlayerLeft::class, function ($event) use ($room) {
            return $event->roomCode === $room->code && $event->playerId === 'p1';
        });
    }

    #[Test]
    public function it_decrements_current_players_count_on_leave(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'DEC',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        $this->service->joinRoom($room->code, 'p2', 'Player 2');
        $this->assertEquals(2, TriviaRoom::find($room->id)->current_players);

        $this->service->leaveRoom($room->code, 'p1');
        
        $this->assertEquals(1, TriviaRoom::find($room->id)->current_players);
    }

    #[Test]
    public function it_removes_player_from_redis_on_leave(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'REMOVE',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        $this->service->leaveRoom($room->code, 'p1');

        $players = Redis::hgetall('trivia:room:REMOVE:players');
        $this->assertEmpty($players);
    }

    #[Test]
    public function it_validates_room_capacity(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'max_players' => 5,
            'code' => 'CAP',
        ]);

        $this->assertTrue($this->service->validateCapacity($room->code));

        // Llenar la sala
        for ($i = 1; $i <= 5; $i++) {
            $this->service->joinRoom($room->code, "p{$i}", "Player {$i}");
        }

        $this->assertFalse($this->service->validateCapacity($room->code));
    }

    #[Test]
    public function it_checks_player_presence_in_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'CHECK',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');

        $this->assertTrue($this->service->isPlayerInRoom($room->code, 'p1'));
        $this->assertFalse($this->service->isPlayerInRoom($room->code, 'p2'));
    }

    #[Test]
    public function it_lists_active_players_in_room(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'LIST',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        $this->service->joinRoom($room->code, 'p2', 'Player 2');

        $players = $this->service->getRoomPlayers($room->code);

        $this->assertCount(2, $players);
        $this->assertContains('p1', array_column($players, 'id'));
        $this->assertContains('p2', array_column($players, 'id'));
    }

    #[Test]
    public function it_sets_room_ttl_in_redis(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'TTL',
        ]);

        $ttl = Redis::ttl('trivia:room:TTL');
        
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(7200, $ttl); // 2 horas
    }

    #[Test]
    public function it_handles_concurrent_player_joins(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'max_players' => 100,
            'code' => 'CONC',
        ]);

        // Simular múltiples joins concurrentes
        for ($i = 1; $i <= 50; $i++) {
            $this->service->joinRoom($room->code, "player_{$i}", "Player {$i}");
        }

        $finalRoom = TriviaRoom::find($room->id);
        $this->assertEquals(50, $finalRoom->current_players);
        
        $players = $this->service->getRoomPlayers($room->code);
        $this->assertCount(50, $players);
    }

    #[Test]
    public function it_prevents_duplicate_player_join(): void
    {
        $room = $this->service->createRoom([
            'host_id' => 'host_123',
            'code' => 'DUP',
        ]);

        $this->service->joinRoom($room->code, 'p1', 'Player 1');
        
        // Intentar unirse nuevamente con mismo ID
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Player already in room');

        $this->service->joinRoom($room->code, 'p1', 'Player 1 Updated');
    }
}
