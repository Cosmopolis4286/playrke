<?php

namespace Database\Seeders;

use App\Models\Games\Common\Utils\GameConfig;
use Illuminate\Database\Seeder;

class GameConfigSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['name' => 'BINGO',  'status' => 'ACTIVE'],
            ['name' => 'POLLA',  'status' => 'ACTIVE'],
            ['name' => 'TRIVIA', 'status' => 'ACTIVE'],
        ];

        foreach ($games as $game) {
            GameConfig::updateOrCreate(
                ['name' => $game['name']],
                ['status' => $game['status']]
            );
        }
    }
}
