<?php

declare(strict_types=1);

use App\Models\MovementType;
use Illuminate\Database\Migrations\Migration;

/*
 * Rechazar un retiro devolvía el saldo sin dejar movimiento: el historial del
 * jugador no lo mostraba y la conciliación de saldos (balance vs. último
 * balance_after) lo marcaba como drift. Ahora el reintegro es un movimiento
 * propio, WITHDRAWAL_REVERSAL (ver WalletService::rejectWithdrawal()).
 */
return new class extends Migration
{
    public function down() : void
    {
        MovementType::where('code', 'WITHDRAWAL_REVERSAL')
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('wallet_movements')->whereColumn('wallet_movements.type', 'movement_types.code'))
            ->delete();
        MovementType::forgetCatalogCache();
    }

    public function up() : void
    {
        MovementType::firstOrCreate(['code' => 'WITHDRAWAL_REVERSAL'], [
            'label' => 'Reintegro de retiro rechazado',
            'icon' => 'fas fa-rotate-left',
            'color' => '#0891b2',
            'is_credit' => true,
            'requires_approval' => false,
            'game_group' => null,
            'sort_order' => 75,
            'visible_roles' => null,
            'is_system' => true,
            'status' => 'ACTIVE',
        ]);
        MovementType::forgetCatalogCache();
    }
};
