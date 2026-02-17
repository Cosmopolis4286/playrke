<?php

namespace Database\Seeders;

use App\Models\Games\Common\Room\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'PRIVATE', 'label' => 'Privada'],
            ['code' => 'PUBLIC', 'label' => 'Pública'],
            ['code' => 'VIP', 'label' => 'VIP'],
            ['code' => 'MIXED', 'label' => 'Mixta'],
        ];

        foreach ($types as $type) {
            RoomType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'label' => $type['label'],
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}
