<?php

namespace Database\Seeders;

use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Database\Seeder;

class GameStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 'NONE',        'label' => 'N/D'],
            ['id' => 'DRAFT',       'label' => 'Borrador'],
            ['id' => 'ACTIVE',      'label' => 'Activa'],
            ['id' => 'INACTIVE',    'label' => 'Inactiva'],
            ['id' => 'DELETED',     'label' => 'Eliminada'],
            ['id' => 'UNAVAILABLE', 'label' => 'No Disponible'],
            ['id' => 'AVAILABLE',   'label' => 'Disponible'],
            ['id' => 'CLOSED',      'label' => 'Cerrada'],
            ['id' => 'IN_PROGRESS', 'label' => 'En Juego'],
            ['id' => 'FINISHED',    'label' => 'Finalizada'],
            ['id' => 'REFUND',      'label' => 'Reembolso'],
            ['id' => 'OFFLINE',     'label' => 'Fuera de Línea'],
        ];

        // Inserción masiva usando upsert para evitar duplicados
        GameStatus::upsert(
            $statuses,
            ['id'],              // columna única
            ['label', 'created_at', 'updated_at'] // campos a actualizar si ya existe
        );
    }
}
