<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['name' => 'BINGO'],
            ['name' => 'POLLA'],
            ['name' => 'TRIVIA'],
        ];

        foreach ($games as $game) {
            Game::updateOrCreate(
                ['name' => $game['name']],
                ['status' => 'ACTIVE']
            );
        }
    }
}
