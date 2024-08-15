<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameModesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['id' => 1, 'description' => 'Online', 'status' => 1],
            ['id' => 2, 'description' => 'Offline', 'status' => 1],
        ];

        // Insertar los datos en la tabla 'game_modes'
        DB::table('game_modes')->insert($data);
    }
}
