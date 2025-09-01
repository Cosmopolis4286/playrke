<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Games;
use Illuminate\Database\Seeder;

class GamesSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la tabla 'games' con los juegos base.
     *
     * @return void
     */
    public function run(): void
    {
        // Lista de juegos predefinidos
        $games = [
            ['name' => 'BINGO',  'status' => 1],
            ['name' => 'TRIVIA', 'status' => 1],
            ['name' => 'POLLA',  'status' => 1],
        ];

        // Inserta o actualiza los registros según el nombre del juego para evitar duplicados
        foreach ($games as $game) {
            Games::updateOrCreate(
                ['name' => $game['name']], // campo único
                ['status' => $game['status']]
            );
        }
    }
}
