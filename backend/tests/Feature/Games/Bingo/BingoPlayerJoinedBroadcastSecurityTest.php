<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Events\BingoPlayerJoined;
use Tests\TestCase;

class BingoPlayerJoinedBroadcastSecurityTest extends TestCase
{
    public function test_public_player_joined_payload_contains_only_room_count_metadata() : void
    {
        $event = new BingoPlayerJoined(
            roomId: 123,
            playerId: 987654,
            playerName: 'Private Player Name',
            currentPlayers: 8,
        );

        $payload = $event->broadcastWith();

        $this->assertSame(123, $payload['room_id']);
        $this->assertSame(8, $payload['current_players']);
        $this->assertArrayNotHasKey('player', $payload);
        $this->assertArrayNotHasKey('player_id', $payload);
        $this->assertArrayNotHasKey('player_name', $payload);
        $this->assertStringNotContainsString('987654', json_encode($payload));
        $this->assertStringNotContainsString('Private Player Name', json_encode($payload));
    }
}
