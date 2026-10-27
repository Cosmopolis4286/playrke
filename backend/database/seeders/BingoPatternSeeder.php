<?php

namespace Database\Seeders;

use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use Illuminate\Database\Seeder;

class BingoPatternSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        // num_id identifica cada patrón de forma estable.
        // Los patrones MULTIPLE referencian num_id de otros patrones en su campo boxes:
        //   LINEA VERTICAL   (num_id=12) → boxes de las 5 columnas verticales (num_id 2-6)
        //   LINEA HORIZONTAL (num_id=13) → boxes de las 5 filas horizontales   (num_id 7-11)
        //   LINEAS           (num_id=14) → boxes: '12;13'  (vertical + horizontal)
        //   DIAGONAL B1-O5   (num_id=15) → patrón UNIQUE
        //   DIAGONAL B5-O1   (num_id=16) → patrón UNIQUE
        //   DIAGONALES       (num_id=17) → boxes: '15;16'  (ambas diagonales)
        $patterns = [
            ['num_id' => 1, 'type_pattern' => 'UNIQUE',   'name' => 'FULL HOUSE',       'boxes' => 'ALL',              'status' => 'ACTIVE'],
            ['num_id' => 2, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA B1-B5',      'boxes' => 'B1;B2;B3;B4;B5',  'status' => 'ACTIVE'],
            ['num_id' => 3, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA I1-I5',      'boxes' => 'I1;I2;I3;I4;I5',  'status' => 'ACTIVE'],
            ['num_id' => 4, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA N1-N5',      'boxes' => 'N1;N2;N4;N5',     'status' => 'ACTIVE'],
            ['num_id' => 5, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA G1-G5',      'boxes' => 'G1;G2;G3;G4;G5',  'status' => 'ACTIVE'],
            ['num_id' => 6, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA O1-O5',      'boxes' => 'O1;O2;O3;O4;O5',  'status' => 'ACTIVE'],
            ['num_id' => 7, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA B1-O1',      'boxes' => 'B1;I1;N1;G1;O1',  'status' => 'ACTIVE'],
            ['num_id' => 8, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA B2-O2',      'boxes' => 'B2;I2;N2;G2;O2',  'status' => 'ACTIVE'],
            ['num_id' => 9, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA B3-O3',      'boxes' => 'B3;I3;G3;O3',     'status' => 'ACTIVE'],
            ['num_id' => 10, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA B4-O4',      'boxes' => 'B4;I4;N4;G4;O4',  'status' => 'ACTIVE'],
            ['num_id' => 11, 'type_pattern' => 'UNIQUE',   'name' => 'LINEA B5-O5',      'boxes' => 'B5;I5;N5;G5;O5',  'status' => 'ACTIVE'],
            ['num_id' => 12, 'type_pattern' => 'MULTIPLE', 'name' => 'LINEA VERTICAL',   'boxes' => '2;3;4;5;6',        'status' => 'ACTIVE'],
            ['num_id' => 13, 'type_pattern' => 'MULTIPLE', 'name' => 'LINEA HORIZONTAL', 'boxes' => '7;8;9;10;11',      'status' => 'ACTIVE'],
            ['num_id' => 14, 'type_pattern' => 'MULTIPLE', 'name' => 'LINEAS',           'boxes' => '12;13',            'status' => 'ACTIVE'],
            ['num_id' => 15, 'type_pattern' => 'UNIQUE',   'name' => 'DIAGONAL B1-O5',   'boxes' => 'B1;I2;G4;O5',     'status' => 'ACTIVE'],
            ['num_id' => 16, 'type_pattern' => 'UNIQUE',   'name' => 'DIAGONAL B5-O1',   'boxes' => 'B5;I4;G2;O1',     'status' => 'ACTIVE'],
            ['num_id' => 17, 'type_pattern' => 'MULTIPLE', 'name' => 'DIAGONALES',       'boxes' => '15;16',            'status' => 'ACTIVE'],
        ];

        // num_id es la clave estable de búsqueda (user_id=NULL → patrones de sistema).
        // Usar num_id como criterio evita duplicados si el name cambia entre versiones.
        foreach ($patterns as $pattern) {
            BingoPattern::updateOrCreate(
                ['num_id' => $pattern['num_id'], 'user_id' => null],
                [
                    'default_pattern' => 'Y',
                    'type_pattern' => $pattern['type_pattern'],
                    'name' => $pattern['name'],
                    'boxes' => $pattern['boxes'],
                    'status' => $pattern['status'],
                ],
            );
        }
    }
}
