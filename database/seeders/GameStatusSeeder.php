<?php

namespace Database\Seeders;

use App\Models\GameStatus;
use Illuminate\Database\Seeder;

class GameStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['value' => 'NONE',        'description' => 'N/D'],
            ['value' => 'AVAILABLE',   'description' => 'Disponible'],
            ['value' => 'IN_PROGRESS', 'description' => 'En Juego'],
            ['value' => 'FINISHED',    'description' => 'Finalizada'],
            ['value' => 'REFUND',      'description' => 'Reembolso'],
            ['value' => 'OFFLINE',     'description' => 'Fuera de Línea'],
        ];

        // Usamos upsert para evitar duplicados en caso de que se ejecute varias veces
        GameStatus::upsert(
            $statuses,
            ['value'], // columna única
            ['description'] // si ya existe el value, solo actualizamos la descripción
        );
    }
}
