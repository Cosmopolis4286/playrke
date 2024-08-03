<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JackpotTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['id' => 1, 'description' => 'Acumulativo'],
            ['id' => 2, 'description' => 'Premio'],
            ['id' => 3, 'description' => 'Mixto'],
        ];

        // Insertar los datos en la tabla 'jackpot_type'
        DB::table('jackpot_type')->insert($data);
    }
}
