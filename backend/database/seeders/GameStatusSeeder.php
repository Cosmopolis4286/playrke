<?php

namespace Database\Seeders;

use App\Models\Games\Common\Utils\GameStatus;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class GameStatusSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $timestamp = now();

        $statuses = [
            ['value' => 'UNAVAILABLE', 'description' => 'No Disponible'],
            ['value' => 'AVAILABLE',   'description' => 'Disponible'],
            ['value' => 'IN_PROGRESS', 'description' => 'En Juego'],
            ['value' => 'FINISHED',    'description' => 'Finalizada'],
            ['value' => 'REFUND',      'description' => 'Reembolso'],
            ['value' => 'OFFLINE',     'description' => 'Fuera de Línea'],
            ['value' => 'DELETED',     'description' => 'Eliminada']
        ];

        // Agregar ID Snowflake y timestamps
        foreach ($statuses as &$status) {
            $status['id'] = $snowflake->id();
            $status['created_at'] = $timestamp;
            $status['updated_at'] = $timestamp;
        }

        // Inserción masiva usando upsert para evitar duplicados
        GameStatus::upsert(
            $statuses,
            ['value'],              // columna única
            ['description', 'id', 'created_at', 'updated_at'] // campos a actualizar si ya existe
        );
    }
}
