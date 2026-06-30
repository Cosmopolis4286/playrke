<?php

namespace Database\Seeders;

use App\Models\MovementType;
use Illuminate\Database\Seeder;

class MovementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'DEPOSIT',        'label' => 'Depósito',           'icon' => 'fas fa-arrow-down', 'color' => '#16a34a', 'is_credit' => true,  'requires_approval' => true,  'game_group' => null,     'sort_order' => 10, 'visible_roles' => null],
            ['code' => 'WITHDRAWAL',     'label' => 'Retiro',             'icon' => 'fas fa-arrow-up',   'color' => '#dc2626', 'is_credit' => false, 'requires_approval' => true,  'game_group' => null,     'sort_order' => 20, 'visible_roles' => null],
            ['code' => 'PURCHASE',       'label' => 'Compra de cartón',   'icon' => 'fas fa-ticket-alt', 'color' => '#e11d48', 'is_credit' => false, 'requires_approval' => false, 'game_group' => 'BINGO',  'sort_order' => 30, 'visible_roles' => null],
            ['code' => 'PRIZE',          'label' => 'Premio ganado',      'icon' => 'fas fa-trophy',     'color' => '#d97706', 'is_credit' => true,  'requires_approval' => false, 'game_group' => 'BINGO',  'sort_order' => 40, 'visible_roles' => null],
            ['code' => 'TRIVIA_ENTRY',   'label' => 'Entrada Trivia',     'icon' => 'fas fa-brain',      'color' => '#7c3aed', 'is_credit' => false, 'requires_approval' => false, 'game_group' => 'TRIVIA', 'sort_order' => 50, 'visible_roles' => null],
            ['code' => 'RKE_COMMISSION', 'label' => 'Comisión RKE',       'icon' => 'fas fa-percent',    'color' => '#667eea', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null,     'sort_order' => 60, 'visible_roles' => ['admin', 'superadmin', 'commerce', 'customer']],
        ];

        foreach ($types as $type) {
            MovementType::firstOrCreate(
                ['code' => $type['code']],
                $type + ['is_system' => true, 'status' => 'ACTIVE'],
            );
        }
    }
}
