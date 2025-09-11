<?php

namespace Database\Seeders;

use App\Models\Banks\BankVenezuela;
use Illuminate\Database\Seeder;
use BradieTilley\Snowflake\Snowflake;

class BankVenezuelaSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar los bancos de Venezuela.
     */
    public function run(): void
    {
        $banks = [
            ['name' => 'Venezuela', 'code' => '0102'],
            ['name' => 'Venezolano de Crédito', 'code' => '0104'],
            ['name' => 'Mercantil', 'code' => '0105'],
            ['name' => 'Provincial', 'code' => '0108'],
            ['name' => 'BanCaribe', 'code' => '0114'],
            ['name' => 'Exterior', 'code' => '0115'],
            ['name' => 'Bicentenario', 'code' => '0175'],
            ['name' => 'Caroní', 'code' => '0128'],
            ['name' => 'Banesco', 'code' => '0134'],
            ['name' => 'Sofitasa', 'code' => '0137'],
            ['name' => 'Plaza', 'code' => '0138'],
            ['name' => 'BFC', 'code' => '0151'],
            ['name' => '100% Banco', 'code' => '0156'],
            ['name' => 'DelSur', 'code' => '0157'],
            ['name' => 'Banco del Tesoro', 'code' => '0163'],
            ['name' => 'Banco Agrícola', 'code' => '0166'],
            ['name' => 'BanCrecer', 'code' => '0168'],
            ['name' => 'Mi Banco', 'code' => '0169'],
            ['name' => 'Activo', 'code' => '0171'],
            ['name' => 'Bancamiga', 'code' => '0172'],
            ['name' => 'Banplus', 'code' => '0174'],
            ['name' => 'BNC', 'code' => '0191'],
            ['name' => 'Banfanb', 'code' => '0177'],
        ];

        // Ordenar por código
        $banks = collect($banks)->sortBy('code')->values()->toArray();

        // Instanciar Snowflake
        $snowflake = new Snowflake();

        // Agregar ID Snowflake a cada registro
        $banks = array_map(function ($bank) use ($snowflake) {
            $bank['id'] = $snowflake->id();
            $bank['created_at'] = now();
            $bank['updated_at'] = now();
            return $bank;
        }, $banks);

        // Insertar en la base de datos
        BankVenezuela::insert($banks);
    }
}
