<?php

namespace Database\Seeders;

use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = ['Acumulativo', 'Premio', 'Mixto'];

        foreach ($descriptions as $description) {
            PrizePoolType::updateOrCreate(
                ['description' => $description],
                ['status' => 'ACTIVE']
            );
        }
    }
}
