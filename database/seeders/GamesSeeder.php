<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['id' => 1, 'name' => 'Bingo', 'status' => 1],
            ['id' => 2, 'name' => 'Trivia', 'status' => 1],
        ];

        // Insertar los datos en la tabla 'games'
        DB::table('games')->insert($data);
    }
}
