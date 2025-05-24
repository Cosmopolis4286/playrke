<?php

namespace Database\Seeders;

use App\Models\GamePrizes;
use App\Models\Games;
use Illuminate\Database\Seeder;

class GamePrizesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'game_prizes'
        $gameID = Games::getValueByName('BINGO');

        GamePrizes::insert([
            ['id' => 1, 'description' => 'Cartón Lleno', 'game' => $gameID, 'status' => 1],
            ['id' => 2, 'description' => 'Serial', 'game' => $gameID, 'status' => 1],
            ['id' => 3, 'description' => 'Canto', 'game' => $gameID, 'status' => 1]
        ]);
    }
}
