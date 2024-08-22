<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'document_type'
        DB::table('document_type')->insert([
            ['id' => 1, 'value' => 'V', 'description' => 'Venezolano', 'country' => 234, 'status' => 1],
            ['id' => 2, 'value' => 'E', 'description' => 'Extrangero', 'country' => 234, 'status' => 1],
            ['id' => 3, 'value' => 'J', 'description' => 'Comercio', 'country' => 234, 'status' => 1],
            ['id' => 4, 'value' => 'CPF', 'description' => 'CPF', 'country' => 29, 'status' => 1]
        ]);
    }
}
