<?php

namespace Tests\Unit\Domains\Bingo;

use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomUpdateDTO;
use App\Http\Requests\Games\Bingo\Room\BingoRoomRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Pausa entre rondas configurable por sala: el formulario la envía como
 * roundPauseSeconds (opcional, 0–600 s) y llega a los DTOs; vacío = global.
 */
class BingoRoundPauseConfigTest extends TestCase
{
    // BingoRoomRequest::rules() consulta room_types (y otras tablas de catálogo).
    use RefreshDatabase;

    public function test_dtos_map_round_pause_seconds_and_default_to_null() : void
    {
        $base = ['connectionType' => '1', 'prizePoolType' => '1', 'allowedCurrencies' => ['1']];

        $this->assertSame(90, BingoRoomCreateDTO::fromArray($base + ['roundPauseSeconds' => '90'])->roundPauseSeconds);
        $this->assertNull(BingoRoomCreateDTO::fromArray($base)->roundPauseSeconds);
        $this->assertSame(0, BingoRoomUpdateDTO::fromArray(['roundPauseSeconds' => 0])->roundPauseSeconds);
        $this->assertSame(45, BingoRoomAutomaticCreateDTO::fromArray($base + ['time' => '20:00', 'roundPauseSeconds' => 45])->roundPauseSeconds);
    }

    public function test_request_rule_accepts_empty_and_range_only() : void
    {
        $rule = ['roundPauseSeconds' => (new BingoRoomRequest)->rules()['roundPauseSeconds']];

        foreach ([null, 0, 120, 600] as $ok) {
            $this->assertTrue(Validator::make(['roundPauseSeconds' => $ok], $rule)->passes(), var_export($ok, true));
        }
        foreach ([-1, 601, 'abc', 1.5] as $bad) {
            $this->assertTrue(Validator::make(['roundPauseSeconds' => $bad], $rule)->fails(), var_export($bad, true));
        }
    }
}
