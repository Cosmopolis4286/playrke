<?php

namespace Database\Seeders;

use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Database\Seeder;

class GameStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'NONE',        'label' => 'N/D'],
            ['code' => 'DRAFT',       'label' => 'Borrador'],
            ['code' => 'ACTIVE',      'label' => 'Activa'],
            ['code' => 'INACTIVE',    'label' => 'Inactiva'],
            ['code' => 'DELETED',     'label' => 'Eliminada'],
            ['code' => 'UNAVAILABLE', 'label' => 'No Disponible'],
            ['code' => 'AVAILABLE',   'label' => 'Disponible'],
            ['code' => 'CLOSED',      'label' => 'Cerrada'],
            ['code' => 'IN_PROGRESS', 'label' => 'En Juego'],
            ['code' => 'FINISHED',    'label' => 'Finalizada'],
            ['code' => 'REFUND',      'label' => 'Reembolso'],
            ['code' => 'OFFLINE',     'label' => 'Fuera de Línea'],
        ];

        // Insertar o actualizar cada status de forma idempotente
        foreach ($statuses as $status) {
            GameStatus::updateOrCreate(
                ['code' => $status['code']],
                [
                    'label' => $status['label']
                ]
            );
        }
    }
}
