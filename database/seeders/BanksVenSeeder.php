<?php

namespace Database\Seeders;

use App\Models\Banks\BankVenezuela;
use Illuminate\Database\Seeder;

class BanksVenezuelaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'banks_ven'
       BankVenezuela::insert([
            ['id' => 1, 'name' => 'Venezuela', 'code' => '0102'],
            ['id' => 2, 'name' => 'Venezolano de Crédito', 'code' => '0104'],
            ['id' => 3, 'name' => 'Mercantil', 'code' => '0105'],
            ['id' => 4, 'name' => 'Provincial', 'code' => '0108'],
            ['id' => 5, 'name' => 'BanCaribe', 'code' => '0114'],
            ['id' => 6, 'name' => 'Exterior', 'code' => '0115'],
            ['id' => 7, 'name' => 'Bicentenario', 'code' => '0175'],
            ['id' => 8, 'name' => 'Caroní', 'code' => '0128'],
            ['id' => 9, 'name' => 'Banesco', 'code' => '0134'],
            ['id' => 10, 'name' => 'Sofitasa', 'code' => '0137'],
            ['id' => 11, 'name' => 'Plaza', 'code' => '0138'],
            ['id' => 12, 'name' => 'BFC', 'code' => '0151'],
            ['id' => 13, 'name' => '100% Banco', 'code' => '0156'],
            ['id' => 14, 'name' => 'DelSur', 'code' => '0157'],
            ['id' => 15, 'name' => 'Banco del Tesoro', 'code' => '0163'],
            ['id' => 16, 'name' => 'Banco Agrícola', 'code' => '0166'],
            ['id' => 17, 'name' => 'BanCrecer', 'code' => '0168'],
            ['id' => 18, 'name' => 'Mi Banco', 'code' => '0169'],
            ['id' => 19, 'name' => 'Activo', 'code' => '0171'],
            ['id' => 20, 'name' => 'Bancamiga', 'code' => '0172'],
            ['id' => 21, 'name' => 'Banplus', 'code' => '0174'],
            ['id' => 22, 'name' => 'BNC', 'code' => '0191'],
            ['id' => 23, 'name' => 'Banfanb', 'code' => '0177']
        ]);
    }
}
