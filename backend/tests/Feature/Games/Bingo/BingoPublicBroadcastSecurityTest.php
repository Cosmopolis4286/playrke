<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Events\BingoGameEnded;
use App\Domains\Games\Bingo\Events\BingoWinnerDeclared;
use Tests\TestCase;

class BingoPublicBroadcastSecurityTest extends TestCase
{
    public function test_game_ended_public_payload_does_not_expose_winner_or_card_details() : void
    {
        $event = new BingoGameEnded(
            roomId: 123,
            drawId: 456,
            hasWinner: true,
            totalDrawn: 37,
            winners: [[
                'user_id' => 987,
                'user_name' => 'Private Winner',
                'card_id' => 654,
                'card_serial' => 'SERIAL-SECRET',
                'prize' => 250.00,
            ]],
            prize: 250.00,
            endTime: '2026-10-24T12:00:00Z',
        );

        $payload = $event->broadcastWith();

        $this->assertSame(123, $payload['room_id']);
        $this->assertSame(456, $payload['draw_id']);
        $this->assertTrue($payload['has_winner']);
        $this->assertTrue($payload['results_available']);
        $this->assertArrayNotHasKey('winners', $payload);
        $this->assertArrayNotHasKey('prize', $payload);
        $this->assertStringNotContainsString('Private Winner', json_encode($payload));
        $this->assertStringNotContainsString('SERIAL-SECRET', json_encode($payload));
    }

    public function test_winner_declared_public_payload_does_not_identify_the_winner() : void
    {
        $event = new BingoWinnerDeclared(
            roomId: 123,
            drawId: 456,
            winnerId: 987,
            winnerName: 'Private Winner',
            cardId: 654,
            pattern: 'FULL_HOUSE',
            prize: 250.00,
        );

        $payload = $event->broadcastWith();

        $this->assertSame(123, $payload['room_id']);
        $this->assertSame(456, $payload['draw_id']);
        $this->assertArrayNotHasKey('winner', $payload);
        $this->assertArrayNotHasKey('winner_id', $payload);
        $this->assertArrayNotHasKey('prize', $payload);
        $this->assertStringNotContainsString('Private Winner', json_encode($payload));
        $this->assertStringNotContainsString('FULL_HOUSE', json_encode($payload));
    }
}
