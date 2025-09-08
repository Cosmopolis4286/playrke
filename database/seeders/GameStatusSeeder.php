<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Utils\GameStatus;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class GameStatusSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $timestamp = now();

        $statuses = [
            ['value' => 'NONE',        'description' => 'N/D'],
            ['value' => 'AVAILABLE',   'description' => 'Disponible'],
            ['value' => 'IN_PROGRESS', 'description' => 'En Juego'],
            ['value' => 'FINISHED',    'description' => 'Finalizada'],
            ['value' => 'REFUND',      'description' => 'Reembolso'],
            ['value' => 'OFFLINE',     'description' => 'Fuera de Línea'],
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
