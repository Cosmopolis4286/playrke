<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BingoDraws;
use Illuminate\Support\Facades\DB;

class GetLastWinningChipTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_if_no_chip_index()
    {
        $service = new \App\Services\BingoServiceFake();

        $result = $service->callGetLastWinningChip(1, 1, 1);
        $this->assertNull($result);
    }

    public function test_returns_last_chip_that_caused_win_in_unique_mode()
    {
        $roomId = 10;
        $round = 1;

        BingoDraws::insert([
            ['room' => $roomId, 'round' => $round, 'chip' => 'B5'],
            ['room' => $roomId, 'round' => $round, 'chip' => 'I17'],
            ['room' => $roomId, 'round' => $round, 'chip' => 'N31'],
        ]);

        $chipIndex = [
            'UNIQUE' => [
                'column1' => [
                    'B5' => [1],
                    'I17' => [2],
                ],
                'column2' => [
                    'N31' => [3],
                ]
            ]
        ];

        $service = new \App\Services\BingoServiceFake($chipIndex);

        $result = $service->callGetLastWinningChip($roomId, $round, 'UNIQUE');
        $this->assertEquals('N31', $result);
    }

    public function test_returns_last_chip_that_caused_win_in_group_mode()
    {
        $roomId = 15;
        $round = 2;

        BingoDraws::insert([
            ['room' => $roomId, 'round' => $round, 'chip' => 'G50'],
            ['room' => $roomId, 'round' => $round, 'chip' => 'O70'],
            ['room' => $roomId, 'round' => $round, 'chip' => 'N35'],
        ]);

        $chipIndex = [
            'GROUP' => [
                'group1' => [
                    'col1' => [
                        'G50' => [1],
                        'O70' => [2],
                    ]
                ],
                'group2' => [
                    'col1' => [
                        'N35' => [3],
                    ]
                ]
            ]
        ];

        $service = new \App\Services\BingoServiceFake($chipIndex);

        $result = $service->callGetLastWinningChip($roomId, $round, 'GROUP');
        $this->assertEquals('N35', $result);
    }
}
