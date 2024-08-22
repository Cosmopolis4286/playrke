<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'game_status'
        DB::table('game_status')->insert([
            ['id' => 1, 'description' => 'N/D'],
            ['id' => 2, 'description' => 'Disponible'],
            ['id' => 3, 'description' => 'En Juego'],
            ['id' => 4, 'description' => 'Finalizada'],
            ['id' => 5, 'description' => 'Reembolso'],
            ['id' => 6, 'description' => 'Fuera de Línea']
        ]);
    }
}
