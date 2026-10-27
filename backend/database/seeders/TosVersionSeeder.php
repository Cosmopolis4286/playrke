<?php

namespace Database\Seeders;

use App\Models\TosVersion;
use Illuminate\Database\Seeder;

class TosVersionSeeder extends Seeder
{
    public function run() : void
    {
        TosVersion::firstOrCreate(
            ['version' => '1.0.0'],
            [
                'title' => 'Términos y Condiciones de Uso',
                'content_key' => 'tos.v1',
                'summary' => 'Versión inicial de los Términos de Servicio de Playrke.',
                'status' => 'active',
                'effective_at' => now(),
            ],
        );
    }
}
