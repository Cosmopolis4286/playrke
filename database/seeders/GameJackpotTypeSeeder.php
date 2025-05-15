<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameTypePrizePoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'game_type_prize_pool'
        DB::table('game_type_prize_pool')->insert([
            ['game' => 1, 'game_mode' => 2, 'type_prize_pool' => 1, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'type_prize_pool' => 2, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'type_prize_pool' => 3, 'status' => 1]
        ]);
    }
}
