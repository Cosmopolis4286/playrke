<?php

namespace Database\Seeders;

use App\Models\Games\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            ['name' => 'BINGO',  'status' => 'ACTIVE'],
            ['name' => 'POLLA',  'status' => 'ACTIVE'],
            ['name' => 'TRIVIA', 'status' => 'ACTIVE'],
        ];

        foreach ($games as $game) {
            Game::updateOrCreate(
                ['name' => $game['name']],
                ['status' => $game['status']]
            );
        }
    }
}
