<?php

namespace Database\Seeders;

use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 'ACCUMULATIVE', 'description' => 'Bote Acumulativo'],
            ['id' => 'FIXED',        'description' => 'Bote Fijo'],
            ['id' => 'MIXED',        'description' => 'Bote Mixto'],
            ['id' => 'PRIZE',        'description' => 'Premio o Servicio'],
        ];

        foreach ($types as $type) {
            PrizePoolType::updateOrCreate(
                ['id' => $type['id']],
                [
                    'description' => $type['description'],
                    'status'      => 'ACTIVE',
                ]
            );
        }
    }
}
