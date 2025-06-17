<?php

namespace Database\Seeders;

use App\Models\BingoTypesPlays;
use Illuminate\Database\Seeder;

class BingoTypesPlaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plays = [
            ['type_play' => 'UNIQUE', 'name' => 'FULL HOUSE', 'boxes' => 'ALL', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA B1-B5', 'boxes' => 'B1;B2;B3;B4;B5', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA I1-I5', 'boxes' => 'I1;I2;I3;I4;I5', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA N1-N5', 'boxes' => 'N1;N2;N4;N5', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA G1-G5', 'boxes' => 'G1;G2;G3;G4;G5', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA O1-O5', 'boxes' => 'O1;O2;O3;O4;O5', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA B1-O1', 'boxes' => 'B1;I1;N1;G1;O1', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA B2-O2', 'boxes' => 'B2;I2;N2;G2;O2', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA B3-03', 'boxes' => 'B3;I3;G3;O3', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA B4-O4', 'boxes' => 'B4;I4;N4;G4;O4', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'LINEA B5-O5', 'boxes' => 'B5;I5;N5;G5;O5', 'status' => 1],
            ['type_play' => 'GROUP', 'name' => 'LINEAS V', 'boxes' => '2;3;4;5;6', 'status' => 1],
            ['type_play' => 'GROUP', 'name' => 'LINEAS H', 'boxes' => '7;8;9;10;11', 'status' => 1],
            ['type_play' => 'GROUP', 'name' => 'LINEAS', 'boxes' => '12;13', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'DIAGONAL B1-O5', 'B1;I2;G4;O5' => 'ALL', 'status' => 1],
            ['type_play' => 'UNIQUE', 'name' => 'DIAGONAL B5-O1', 'B5;I4;G2;O1' => 'ALL', 'status' => 1],
            ['type_play' => 'GROUP', 'name' => 'DIAGONALES', 'boxes' => '15;16', 'status' => 1],
        ];

        // Insertar los datos en la tabla 'bingo_types_plays'
        BingoTypesPlays::insert($plays);
    }
}
