<?php

namespace Database\Seeders;

use App\Models\MovementType;
use Illuminate\Database\Seeder;

class MovementTypeSeeder extends Seeder
{
    public function run() : void
    {
        // PRIZE queda con game_group null (transversal a todos los juegos:
        // Bingo, Trivia, etc. otorgan premio con el mismo tipo) — se muestra
        // una sola vez en la sección "General" en vez de duplicarse por juego.
        $types = [
            ['code' => 'DEPOSIT',        'label' => 'Depósito',           'icon' => 'fas fa-arrow-down', 'color' => '#16a34a', 'is_credit' => true,  'requires_approval' => true,  'game_group' => null,     'sort_order' => 10, 'visible_roles' => null],
            ['code' => 'WITHDRAWAL',     'label' => 'Retiro',             'icon' => 'fas fa-arrow-up',   'color' => '#dc2626', 'is_credit' => false, 'requires_approval' => true,  'game_group' => null,     'sort_order' => 20, 'visible_roles' => null],
            ['code' => 'PRIZE',          'label' => 'Premio ganado',      'icon' => 'fas fa-trophy',     'color' => '#d97706', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null,     'sort_order' => 30, 'visible_roles' => null],
            ['code' => 'BONUS_PRIZE',    'label' => 'Premio bono (no retirable)', 'icon' => 'fas fa-gift', 'color' => '#a855f7', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null,     'sort_order' => 35, 'visible_roles' => null],
            ['code' => 'PURCHASE',       'label' => 'Compra de cartón',   'icon' => 'fas fa-ticket-alt', 'color' => '#e11d48', 'is_credit' => false, 'requires_approval' => false, 'game_group' => 'BINGO',  'sort_order' => 40, 'visible_roles' => null],
            ['code' => 'TRIVIA_ENTRY',   'label' => 'Entrada Trivia',     'icon' => 'fas fa-brain',      'color' => '#7c3aed', 'is_credit' => false, 'requires_approval' => false, 'game_group' => 'TRIVIA', 'sort_order' => 50, 'visible_roles' => null],
            ['code' => 'RKE_COMMISSION', 'label' => 'Comisión RKE',       'icon' => 'fas fa-percent',    'color' => '#667eea', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null,     'sort_order' => 60, 'visible_roles' => ['admin', 'superadmin', 'commerce', 'customer']],
            // Reversa del RKE de una venta que se reembolsó (sala cancelada / pago rechazado).
            ['code' => 'RKE_COMMISSION_REVERSAL', 'label' => 'Reversión comisión RKE', 'icon' => 'fas fa-rotate-left', 'color' => '#b91c1c', 'is_credit' => false, 'requires_approval' => false, 'game_group' => null, 'sort_order' => 65, 'visible_roles' => ['admin', 'superadmin']],
            // Comisión del creador de la sala (+ fondos del premio en Premio/Servicio puras) al cerrar la sala.
            // Cobro al comercio del bote/jackpot de ventas fuera del sistema (y su devolución si el lote se reembolsa).
            ['code' => 'BINGO_POOL_FUNDING', 'label' => 'Fondeo de bote/jackpot (cobro a comercio)', 'icon' => 'fas fa-piggy-bank', 'color' => '#0891b2', 'is_credit' => true, 'requires_approval' => false, 'game_group' => 'BINGO', 'sort_order' => 67, 'visible_roles' => ['admin', 'superadmin']],
            ['code' => 'BINGO_POOL_FUNDING_REVERSAL', 'label' => 'Devolución de fondeo de bote/jackpot', 'icon' => 'fas fa-rotate-left', 'color' => '#b91c1c', 'is_credit' => false, 'requires_approval' => false, 'game_group' => 'BINGO', 'sort_order' => 68, 'visible_roles' => ['admin', 'superadmin']],
            ['code' => 'CREATOR_COMMISSION', 'label' => 'Comisión de sala (creador)', 'icon' => 'fas fa-hand-holding-dollar', 'color' => '#059669', 'is_credit' => true, 'requires_approval' => false, 'game_group' => 'BINGO', 'sort_order' => 66, 'visible_roles' => null],
            ['code' => 'REFUND',         'label' => 'Reembolso',          'icon' => 'fas fa-undo',       'color' => '#0891b2', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null,     'sort_order' => 70, 'visible_roles' => null],
            ['code' => 'WITHDRAWAL_REVERSAL', 'label' => 'Reintegro de retiro rechazado', 'icon' => 'fas fa-rotate-left', 'color' => '#0891b2', 'is_credit' => true, 'requires_approval' => false, 'game_group' => null, 'sort_order' => 75, 'visible_roles' => null],
            ['code' => 'PRIZE_REVERSAL', 'label' => 'Reversión de premio (disputa)', 'icon' => 'fas fa-gavel', 'color' => '#b91c1c', 'is_credit' => false, 'requires_approval' => false, 'game_group' => null, 'sort_order' => 80, 'visible_roles' => ['admin', 'superadmin']],
            ['code' => 'MARKETPLACE_PURCHASE', 'label' => 'Compra en marketplace', 'icon' => 'fas fa-cart-shopping', 'color' => '#e11d48', 'is_credit' => false, 'requires_approval' => false, 'game_group' => null, 'sort_order' => 90, 'visible_roles' => null],
            ['code' => 'MARKETPLACE_SALE',     'label' => 'Venta en marketplace',  'icon' => 'fas fa-store',         'color' => '#059669', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null, 'sort_order' => 100, 'visible_roles' => null],
            ['code' => 'MARKETPLACE_FEE',      'label' => 'Comisión de marketplace', 'icon' => 'fas fa-percent',    'color' => '#667eea', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null, 'sort_order' => 110, 'visible_roles' => ['admin', 'superadmin']],
            ['code' => 'CRYPTO_DEPOSIT',       'label' => 'Depósito cripto',         'icon' => 'fab fa-bitcoin',    'color' => '#f59e0b', 'is_credit' => true,  'requires_approval' => false, 'game_group' => null, 'sort_order' => 120, 'visible_roles' => null],
            ['code' => 'CRYPTO_WITHDRAWAL',    'label' => 'Retiro cripto',           'icon' => 'fab fa-bitcoin',    'color' => '#dc2626', 'is_credit' => false, 'requires_approval' => false, 'game_group' => null, 'sort_order' => 130, 'visible_roles' => null],
        ];

        foreach ($types as $type) {
            MovementType::firstOrCreate(
                ['code' => $type['code']],
                $type + ['is_system' => true, 'status' => 'ACTIVE'],
            );
        }
    }
}
