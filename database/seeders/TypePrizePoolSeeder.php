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
        // Insertar los datos en la tabla 'prize_pool_types'
        DB::table('prize_pool_types')->insert([
            ['id' => 1, 'description' => 'Acumulativo'],
            ['id' => 2, 'description' => 'Premio'],
            ['id' => 3, 'description' => 'Mixto']
        ]);
    }
}
