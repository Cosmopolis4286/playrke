<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GamePrizePoolTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'game_prize_pool_type'
        DB::table('game_prize_pool_type')->insert([
            ['game' => 1, 'game_mode' => 2, 'prize_pool_type' => 1, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'prize_pool_type' => 2, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'prize_pool_type' => 3, 'status' => 1]
        ]);
    }
}
