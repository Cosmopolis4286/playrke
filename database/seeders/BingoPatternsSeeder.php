<?php

namespace Database\Seeders;

use App\Models\Games\Bingo\Cartons\Patterns\BingoPatterns;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BingoPatternsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $plays = [
            ['type_pattern' => 'UNIQUE', 'name' => 'FULL HOUSE', 'boxes' => 'ALL', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA B1-B5', 'boxes' => 'B1;B2;B3;B4;B5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA I1-I5', 'boxes' => 'I1;I2;I3;I4;I5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA N1-N5', 'boxes' => 'N1;N2;N4;N5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA G1-G5', 'boxes' => 'G1;G2;G3;G4;G5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA O1-O5', 'boxes' => 'O1;O2;O3;O4;O5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA B1-O1', 'boxes' => 'B1;I1;N1;G1;O1', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA B2-O2', 'boxes' => 'B2;I2;N2;G2;O2', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA B3-O3', 'boxes' => 'B3;I3;G3;O3', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA B4-O4', 'boxes' => 'B4;I4;N4;G4;O4', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'LINEA B5-O5', 'boxes' => 'B5;I5;N5;G5;O5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'GROUP', 'name' => 'LINEAS V', 'boxes' => '2;3;4;5;6', 'status' => 'ACTIVE'],
            ['type_pattern' => 'GROUP', 'name' => 'LINEAS H', 'boxes' => '7;8;9;10;11', 'status' => 'ACTIVE'],
            ['type_pattern' => 'GROUP', 'name' => 'LINEAS', 'boxes' => '12;13', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'DIAGONAL B1-O5', 'boxes' => 'B1;I2;G4;O5', 'status' => 'ACTIVE'],
            ['type_pattern' => 'UNIQUE', 'name' => 'DIAGONAL B5-O1', 'boxes' => 'B5;I4;G2;O1', 'status' => 'ACTIVE'],
            ['type_pattern' => 'GROUP', 'name' => 'DIAGONALES', 'boxes' => '15;16', 'status' => 'ACTIVE'],
        ];

        // Agregar timestamps manualmente
        foreach ($plays as &$play) {
            $play['created_at'] = $now;
            $play['updated_at'] = $now;
        }

        // Insertar en la base de datos
        BingoPatterns::insert($plays);
    }
}
