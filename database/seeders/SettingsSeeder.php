<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['description' => 'LOGIN', 'status' => 1],
            ['description' => 'REGISTER', 'status' => 1],
        ];

        // Insertar los datos en la tabla 'settings'
        DB::table('settings')->insert($data);
    }
}
