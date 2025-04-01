<?php

namespace Database\Seeders;

use App\Models\PollaTypes;
use Illuminate\Database\Seeder;

class PollaTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertar los datos en la tabla 'polla_types'
        PollaTypes::insert([
            ['id' => 1, 'name' => 'Polla Express', 'hour_a' => '2', 'hour_b' => '3', 'status' => 1],
            ['id' => 2, 'name' => 'Super Polla', 'hour_a' => '2', 'hour_b' => '3', 'status' => 1],
            ['id' => 3, 'name' => 'Polla Estelar', 'hour_a' => '2', 'hour_b' => '3', 'status' => 1]
        ]);
    }
}
