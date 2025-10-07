<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Game;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $timestamp = now();

        $games = [
            ['name' => 'BINGO',  'status' => 'ACTIVE'],
            ['name' => 'POLLA',  'status' => 'ACTIVE'],
            ['name' => 'TRIVIA', 'status' => 'ACTIVE'],
        ];

        foreach ($games as &$game) {
            $game['id'] = $snowflake->id();   // Genera ID Snowflake
            $game['created_at'] = $timestamp;
            $game['updated_at'] = $timestamp;
        }

        foreach ($games as $game) {
            Game::updateOrCreate(
                ['name' => $game['name']],  // campo único
                $game                        // incluye id y timestamps
            );
        }
    }
}
