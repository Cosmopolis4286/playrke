<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameJackpotTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'game_jackpot_type'
        DB::table('game_jackpot_type')->insert([
            ['game' => 1, 'game_mode' => 2, 'jackpot_type' => 1, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'jackpot_type' => 2, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'jackpot_type' => 3, 'status' => 1]
        ]);
    }
}
