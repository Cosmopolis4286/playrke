<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'settings'
        Settings::insert([
            ['param' => 'LOGIN', 'status' => 1],
            ['param' => 'REGISTER', 'status' => 1]
        ]);
    }
}
