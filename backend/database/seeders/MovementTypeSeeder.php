<?php

namespace Database\Seeders;

use App\Models\MovementType;
use Illuminate\Database\Seeder;

class MovementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'DEPOSIT',        'label' => 'Depósito',           'icon' => 'fas fa-arrow-down', 'color' => '#16a34a', 'is_credit' => true,  'requires_approval' => true],
            ['code' => 'WITHDRAWAL',     'label' => 'Retiro',             'icon' => 'fas fa-arrow-up',   'color' => '#dc2626', 'is_credit' => false, 'requires_approval' => true],
            ['code' => 'PURCHASE',       'label' => 'Compra de cartón',   'icon' => 'fas fa-ticket-alt', 'color' => '#e11d48', 'is_credit' => false, 'requires_approval' => false],
            ['code' => 'PRIZE',          'label' => 'Premio ganado',      'icon' => 'fas fa-trophy',     'color' => '#d97706', 'is_credit' => true,  'requires_approval' => false],
            ['code' => 'TRIVIA_ENTRY',   'label' => 'Entrada Trivia',     'icon' => 'fas fa-brain',      'color' => '#7c3aed', 'is_credit' => false, 'requires_approval' => false],
            ['code' => 'RKE_COMMISSION', 'label' => 'Comisión RKE',       'icon' => 'fas fa-percent',    'color' => '#667eea', 'is_credit' => true,  'requires_approval' => false],
        ];

        foreach ($types as $type) {
            MovementType::firstOrCreate(
                ['code' => $type['code']],
                $type + ['is_system' => true, 'status' => 'ACTIVE'],
            );
        }
    }
}
