<?php

namespace Database\Seeders;

use App\Models\Games\Shared\PrizePool\PrizePoolType;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la tabla 'prize_pool_types' con los tipos de pool de premios.
     *
     * @return void
     */
    public function run(): void
    {
        $entries = [
            ['description' => 'Acumulativo'],
            ['description' => 'Premio'],
            ['description' => 'Mixto'],
        ];

        // Inserta o actualiza los registros para evitar duplicados
        foreach ($entries as $entry) {
            PrizePoolType::updateOrCreate(
                ['description' => $entry['description']],
                $entry
            );
        }
    }
}
