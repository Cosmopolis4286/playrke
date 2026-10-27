<?php

namespace Database\Seeders;

use App\Models\Games\Common\ConnectionType\ConnectionType;
use Illuminate\Database\Seeder;

class ConnectionTypeSeeder extends Seeder
{
    public function run() : void
    {
        $connectionTypes = [
            ['code' => 'ONLINE', 'label' => 'Online'],
            ['code' => 'OFFLINE', 'label' => 'Offline'],
            ['code' => 'MIXED', 'label' => 'Mixto'],
        ];

        // Insertar o actualizar cada tipo de forma idempotente
        foreach ($connectionTypes as $type) {
            ConnectionType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'label' => $type['label'],
                    'status' => 'ACTIVE',
                ],
            );
        }
    }
}
