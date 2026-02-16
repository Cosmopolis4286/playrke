<?php

namespace Database\Seeders;

use App\Models\Common\Room\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 'PRIVATE', 'label' => 'Privada'],
            ['id' => 'PUBLIC', 'label' => 'Pública'],
            ['id' => 'VIP', 'label' => 'VIP'],
            ['id' => 'MIXED', 'label' => 'Mixta'],
        ];

        foreach ($types as $type) {
            RoomType::updateOrCreate(
                ['id' => $type['id']],
                [
                    'label' => $type['label'],
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}
