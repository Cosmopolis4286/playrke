<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoRoomHealthCheckService;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomStateCoordinator;
use Mockery;
use Tests\TestCase;

class BingoRoomHealthCheckServiceTest extends TestCase
{
    public function test_uses_persisted_players_and_cards_when_denormalized_counters_are_stale() : void
    {
        $room = $this->makeRoom(
            denormalizedPlayers: 0,
            denormalizedCards: 0,
            actualPlayers: 3,
            actualCards: 4,
            minPlayers: 2,
            minCards: 2,
        );

        $errors = app(BingoRoomHealthCheckService::class)->collectErrors($room);

        $this->assertSame([], $errors);
    }

    public function test_rejects_room_using_actual_counts_even_when_denormalized_counters_are_high() : void
    {
        $room = $this->makeRoom(
            denormalizedPlayers: 20,
            denormalizedCards: 20,
            actualPlayers: 1,
            actualCards: 1,
            minPlayers: 2,
            minCards: 2,
        );

        $errors = app(BingoRoomHealthCheckService::class)->collectErrors($room);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('mínimo 2 cartones', $errors[0] . $errors[1]);
        $this->assertStringContainsString('mínimo 2 jugadores', $errors[0] . $errors[1]);
        $this->assertStringContainsString('tiene 1', $errors[0]);
        $this->assertStringContainsString('tiene 1', $errors[1]);
    }

    private function makeRoom(
        int $denormalizedPlayers,
        int $denormalizedCards,
        int $actualPlayers,
        int $actualCards,
        int $minPlayers,
        int $minCards,
    ) : BingoRoom {
        $coordinator = Mockery::mock(BingoRoomStateCoordinator::class);
        $coordinator->shouldReceive('countPlayers')
            ->once()
            ->with(Mockery::type(BingoRoom::class))
            ->andReturn($actualPlayers);
        $coordinator->shouldReceive('countEligibleCardsForDraw')
            ->once()
            ->with(Mockery::type(BingoRoom::class))
            ->andReturn($actualCards);
        $this->app->instance(BingoRoomStateCoordinator::class, $coordinator);

        /** @var BingoRoom&\Mockery\MockInterface $room */
        $room = Mockery::mock(BingoRoom::class)->makePartial();
        $room->shouldReceive('loadMissing')
            ->once()
            ->with(['rounds', 'config'])
            ->andReturnSelf();

        $room->setAttribute('id', 'health-check-room');
        $room->setAttribute('total_players', $denormalizedPlayers);
        $room->setAttribute('total_cards', $denormalizedCards);
        $room->setAttribute('prize_pool', 0);
        $room->setRelation('status', (object) ['code' => 'ACTIVE']);
        $room->setRelation('config', (object) [
            'rounds' => 1,
            'min_players' => $minPlayers,
            'min_cards_to_start' => $minCards,
        ]);
        $room->setRelation('rounds', collect([
            (object) [
                'status' => 'ACTIVE',
                'bingo_pattern_id' => 'pattern-1',
                'prize_type' => 'FIXED',
                'round' => 1,
            ],
        ]));

        return $room;
    }
}
