<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Events\BingoPlayersProgress;
use Tests\TestCase;

class BingoPlayersProgressBroadcastSecurityTest extends TestCase
{
    public function test_public_progress_payload_anonymizes_player_identity() : void
    {
        $event = new BingoPlayersProgress(123, [
            ['user_id' => 987654, 'user_name' => 'Private Player', 'missing' => 2],
            ['user_id' => 987655, 'user_name' => 'Another Private Player', 'missing' => 4],
        ]);

        $payload = $event->broadcastWith();

        $this->assertSame(123, $payload['room_id']);
        $this->assertSame([
            ['user_id' => 1, 'user_name' => 'Jugador 1', 'missing' => 2],
            ['user_id' => 2, 'user_name' => 'Jugador 2', 'missing' => 4],
        ], $payload['players']);
        $this->assertStringNotContainsString('987654', json_encode($payload));
        $this->assertStringNotContainsString('Private Player', json_encode($payload));
    }
}
