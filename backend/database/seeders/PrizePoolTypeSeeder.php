<?php

namespace Database\Seeders;

use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'ACCUMULATIVE', 'label' => 'Bote Acumulativo'],
            ['code' => 'FIXED',        'label' => 'Bote Fijo'],
            ['code' => 'MIXED',        'label' => 'Bote Mixto'],
            ['code' => 'PRIZE',        'label' => 'Premio o Servicio'],
        ];

        foreach ($types as $type) {
            PrizePoolType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'label' => $type['label'],
                    'status'      => 'ACTIVE',
                ]
            );
        }
    }
}
