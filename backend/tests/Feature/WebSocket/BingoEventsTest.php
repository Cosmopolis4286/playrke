<?php

namespace Tests\Feature\WebSocket;

use App\Domains\Games\Bingo\Events\BingoNumberCalled;
use App\Domains\Games\Bingo\Events\BingoGameStarted;
use App\Domains\Games\Bingo\Events\BingoGameEnded;
use App\Domains\Games\Bingo\Events\BingoWinnerDeclared;
use App\Domains\Games\Bingo\Events\BingoPlayerJoined;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BingoEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function bingo_number_called_event_can_be_dispatched()
    {
        Event::fake();

        $roomId = 123;
        $number = 42;

        broadcast(new BingoNumberCalled($number, $roomId));

        Event::assertDispatched(BingoNumberCalled::class, function ($event) use ($number, $roomId) {
            return $event->number === $number && $event->roomId === $roomId;
        });
    }

    /** @test */
    public function bingo_number_called_event_broadcasts_on_correct_channel()
    {
        $roomId = 123;
        $event = new BingoNumberCalled(42, $roomId);

        $channel = $event->broadcastOn();

        $this->assertEquals("bingo.room.{$roomId}", $channel->name);
    }

    /** @test */
    public function bingo_number_called_event_has_correct_name()
    {
        $event = new BingoNumberCalled(42, 123);

        $this->assertEquals('BingoNumberCalled', $event->broadcastAs());
    }

    /** @test */
    public function bingo_game_started_event_can_be_dispatched()
    {
        Event::fake();

        $roomId = 123;
        $drawId = 456;

        broadcast(new BingoGameStarted($roomId, $drawId));

        Event::assertDispatched(BingoGameStarted::class, function ($event) use ($roomId, $drawId) {
            return $event->roomId === $roomId && $event->drawId === $drawId;
        });
    }

    /** @test */
    public function bingo_game_started_event_broadcasts_with_correct_data()
    {
        $roomId = 123;
        $drawId = 456;

        $event = new BingoGameStarted($roomId, $drawId);
        $data = $event->broadcastWith();

        $this->assertArrayHasKey('room_id', $data);
        $this->assertArrayHasKey('draw_id', $data);
        $this->assertArrayHasKey('start_time', $data);
        $this->assertEquals($roomId, $data['room_id']);
        $this->assertEquals($drawId, $data['draw_id']);
    }

    /** @test */
    public function bingo_game_ended_event_can_be_dispatched()
    {
        Event::fake();

        broadcast(new BingoGameEnded(
            roomId: 123,
            drawId: 456,
            hasWinner: true,
            totalDrawn: 30,
            winners: [],
            prize: 100.00,
            endTime: now()->toISOString(),
        ));

        Event::assertDispatched(BingoGameEnded::class);
    }

    /** @test */
    public function bingo_game_ended_event_includes_winner_status()
    {
        $event = new BingoGameEnded(
            roomId: 123,
            drawId: 456,
            hasWinner: true,
            totalDrawn: 30,
            winners: [],
            prize: 100.00,
            endTime: now()->toISOString(),
        );
        $data = $event->broadcastWith();

        $this->assertTrue($data['has_winner']);

        $event = new BingoGameEnded(
            roomId: 123,
            drawId: 456,
            hasWinner: false,
            totalDrawn: 30,
            winners: [],
            prize: 0,
            endTime: now()->toISOString(),
        );
        $data = $event->broadcastWith();

        $this->assertFalse($data['has_winner']);
    }

    /** @test */
    public function bingo_winner_declared_event_can_be_dispatched()
    {
        Event::fake();

        broadcast(new BingoWinnerDeclared(
            roomId: 123,
            drawId: 456,
            winnerId: 789,
            winnerName: 'John Doe',
            cardId: 101,
            pattern: 'full_card',
            prize: 1000.00
        ));

        Event::assertDispatched(BingoWinnerDeclared::class);
    }

    /** @test */
    public function bingo_winner_declared_event_broadcasts_winner_details()
    {
        $event = new BingoWinnerDeclared(
            roomId: 123,
            drawId: 456,
            winnerId: 789,
            winnerName: 'John Doe',
            cardId: 101,
            pattern: 'full_card',
            prize: 1000.00
        );

        $data = $event->broadcastWith();

        $this->assertSame(123, $data['room_id']);
        $this->assertSame(456, $data['draw_id']);
        $this->assertArrayHasKey('timestamp', $data);

        // The room channel is public: winner identity, card and prize details
        // must only be exposed by the authorized room-scoped result endpoint.
        $this->assertArrayNotHasKey('winner', $data);
        $this->assertArrayNotHasKey('winner_id', $data);
        $this->assertArrayNotHasKey('winner_name', $data);
        $this->assertArrayNotHasKey('card_id', $data);
        $this->assertArrayNotHasKey('prize', $data);
    }

    /** @test */
    public function bingo_player_joined_event_can_be_dispatched()
    {
        Event::fake();

        broadcast(new BingoPlayerJoined(123, 789, 'John Doe', 10));

        Event::assertDispatched(BingoPlayerJoined::class);
    }

    /** @test */
    public function bingo_player_joined_event_includes_player_count()
    {
        $event = new BingoPlayerJoined(123, 789, 'John Doe', 10);
        $data = $event->broadcastWith();

        $this->assertEquals(10, $data['current_players']);
    }

    /** @test */
    public function all_bingo_events_broadcast_on_correct_channel()
    {
        $roomId = 123;

        $events = [
            new BingoNumberCalled(42, $roomId),
            new BingoGameStarted($roomId, 456),
            new BingoGameEnded(
                roomId: $roomId,
                drawId: 456,
                hasWinner: true,
                totalDrawn: 30,
                winners: [],
                prize: 100.00,
                endTime: now()->toISOString(),
            ),
            new BingoWinnerDeclared($roomId, 456, 789, 'John', 101, 'pattern', 100),
            new BingoPlayerJoined($roomId, 789, 'John', 10),
        ];

        foreach ($events as $event) {
            $channel = $event->broadcastOn();
            $this->assertEquals("bingo.room.{$roomId}", $channel->name);
        }
    }

    /** @test */
    public function all_events_have_correct_broadcast_names()
    {
        $expectedNames = [
            BingoNumberCalled::class => 'BingoNumberCalled',
            BingoGameStarted::class => 'GameStarted',
            BingoGameEnded::class => 'GameEnded',
            BingoWinnerDeclared::class => 'WinnerDeclared',
            BingoPlayerJoined::class => 'PlayerJoined',
        ];

        foreach ($expectedNames as $class => $expectedName) {
            $event = $this->createEventInstance($class);
            $this->assertEquals($expectedName, $event->broadcastAs());
        }
    }

    /** @test */
    public function events_should_broadcast_now()
    {
        $events = [
            new BingoNumberCalled(42, 123),
            new BingoGameStarted(123, 456),
            new BingoGameEnded(
                roomId: 123,
                drawId: 456,
                hasWinner: true,
                totalDrawn: 30,
                winners: [],
                prize: 100.00,
                endTime: now()->toISOString(),
            ),
            new BingoWinnerDeclared(123, 456, 789, 'John', 101, 'pattern', 100),
            new BingoPlayerJoined(123, 789, 'John', 10),
        ];

        foreach ($events as $event) {
            $this->assertInstanceOf(
                \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class,
                $event,
                get_class($event) . ' should implement ShouldBroadcastNow'
            );
        }
    }

    protected function createEventInstance($class)
    {
        return match ($class) {
            BingoNumberCalled::class => new BingoNumberCalled(42, 123),
            BingoGameStarted::class => new BingoGameStarted(123, 456),
            BingoGameEnded::class => new BingoGameEnded(
                roomId: 123,
                drawId: 456,
                hasWinner: true,
                totalDrawn: 30,
                winners: [],
                prize: 100.00,
                endTime: now()->toISOString(),
            ),
            BingoWinnerDeclared::class => new BingoWinnerDeclared(123, 456, 789, 'John', 101, 'pattern', 100),
            BingoPlayerJoined::class => new BingoPlayerJoined(123, 789, 'John', 10),
        };
    }
}
