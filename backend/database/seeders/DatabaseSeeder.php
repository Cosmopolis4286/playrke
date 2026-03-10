<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Llamar a todos los seeders necesarios (orden simplificado)
        $this->call([
            CountrySeeder::class,
            CurrencySeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            DemoRoleDataSeeder::class,
        ]);
    }
}
