<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * wallets ya tiene datos reales cuando se escribió esto — migración
 * incremental, mismo patrón que 2026_08_30_200343_add_triggered_by_to_
 * command_logs_table y 2026_08_31_000000_add_disputed_to_bingo_card_
 * prizes_status_enum.
 *
 * pending_debt: cuando GamePrizeService::disputeAndReverse() revierte un
 * premio ya gastado por el jugador, el débito se topa al balance disponible
 * (chk_wallets_balance_nonneg impide balance negativo — decisión de
 * producto: no romper esa garantía global del sistema) y la diferencia no
 * cobrada queda registrada aquí. WalletService::recordAdminDeposit()/
 * cualquier crédito futuro a la wallet debe descontar esta deuda primero
 * (ver WalletService::settlePendingDebt()) antes de que el usuario vea el
 * saldo nuevo disponible.
 *
 * El Schema::create original también queda actualizado con esta columna
 * para que un entorno nuevo (migrate:fresh) la tenga desde la creación.
 */
return new class extends Migration
{
    public function down() : void
    {
        if (! Schema::hasColumn('wallets', 'pending_debt')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets DROP CHECK chk_wallets_pending_debt_nonneg');
        }

        Schema::table('wallets', function(Blueprint $table) {
            $table->dropColumn('pending_debt');
        });
    }

    public function up() : void
    {
        if (Schema::hasColumn('wallets', 'pending_debt')) {
            return;
        }

        Schema::table('wallets', function(Blueprint $table) {
            $table->decimal('pending_debt', 15, 2)
                ->default(0.00)
                ->after('bonus_balance')
                ->comment('Deuda pendiente de cobro (ej. reversión de premio en disputa que excedió el saldo disponible) — se descuenta automáticamente del próximo crédito a la wallet');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_pending_debt_nonneg CHECK (pending_debt >= 0)');
        }
    }
};
