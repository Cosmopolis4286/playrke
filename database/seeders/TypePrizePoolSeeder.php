<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypePrizePoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'type_prize_pools'
        DB::table('type_prize_pools')->insert([
            ['id' => 1, 'description' => 'Acumulativo'],
            ['id' => 2, 'description' => 'Premio'],
            ['id' => 3, 'description' => 'Mixto']
        ]);
    }
}
