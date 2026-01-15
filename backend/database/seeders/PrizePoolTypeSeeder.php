<?php

namespace Database\Seeders;

use App\Models\Shared\PrizePool\PrizePoolType;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $timestamp = now();

        $entries = [
            ['description' => 'Acumulativo'],
            ['description' => 'Premio'],
            ['description' => 'Mixto'],
        ];

        // Agregar ID Snowflake y timestamps
        foreach ($entries as &$entry) {
            $entry['id'] = $snowflake->id();
            $entry['created_at'] = $timestamp;
            $entry['updated_at'] = $timestamp;
        }

        // Inserta o actualiza los registros para evitar duplicados
        foreach ($entries as $entry) {
            PrizePoolType::updateOrCreate(
                ['description' => $entry['description']], // clave única
                $entry                                     // incluye id y timestamps
            );
        }
    }
}
