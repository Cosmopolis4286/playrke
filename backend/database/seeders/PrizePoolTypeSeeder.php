<?php

namespace Database\Seeders;

use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'ACCUMULATIVE', 'description' => 'Bote Acumulativo'],
            ['code' => 'FIXED',        'description' => 'Bote Fijo'],
            ['code' => 'MIXED',        'description' => 'Bote Mixto'],
            ['code' => 'PRIZE',        'description' => 'Premio o Servicio'],
        ];

        foreach ($types as $type) {
            PrizePoolType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'description' => $type['description'],
                    'status'      => 'ACTIVE',
                ]
            );
        }
    }
}
