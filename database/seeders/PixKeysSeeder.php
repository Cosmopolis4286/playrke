<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PixKeysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['id' => 1, 'value' => 'CPF'],
            ['id' => 2, 'value' => 'Celular'],
            ['id' => 3, 'value' => 'Email'],
            ['id' => 4, 'value' => 'Chave aleatória'],
            ['id' => 5, 'value' => 'CPNJ']
        ];

        // Insertar los datos en la tabla 'pix_keys'
        DB::table('pix_keys')->insert($data);
    }
}
