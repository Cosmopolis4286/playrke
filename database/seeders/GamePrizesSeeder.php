<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        DB::table('game_prizes')->insert(
            ['id' => 1, 'description' => 'Premio Mayor', 'game' => 1, 'status' => 1],
            ['id' => 2, 'description' => 'Serial', 'game' => 1, 'status' => 1]
        );
    }
}
