<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración.
     */
    public function down() : void
    {
        if (! Schema::hasColumn('wallet_movements', 'receipt_hash')) {
            return;
        }

        Schema::table('wallet_movements', function(Blueprint $table) {
            $table->dropIndex(['receipt_hash']);
            $table->dropColumn('receipt_hash');
        });
    }

    /**
     * Ejecutar la migración: agregar receipt_hash a wallet_movements.
     *
     * sha256 del archivo de comprobante subido en un depósito — permite al
     * panel admin (WalletMovementController) detectar reuso del mismo
     * comprobante (captura de pantalla) en otro depósito, la señal de
     * fraude más común en pagos con comprobante manual.
     */
    public function up() : void
    {
        // Guard de idempotencia — mismo patrón que
        // 2026_08_31_000100_add_pending_debt_to_wallets_table: sin esto, un
        // migrate:fresh que corre en paralelo con otra sesión que ya deja la
        // columna físicamente creada pero sin registrar en `migrations`
        // (crash a mitad de camino, RefreshDatabase de otra sesión pisando
        // el mismo schema) revienta con "Column already exists" y aborta
        // toda la corrida de tests antes de llegar al seeder.
        if (Schema::hasColumn('wallet_movements', 'receipt_hash')) {
            return;
        }

        Schema::table('wallet_movements', function(Blueprint $table) {
            $table->char('receipt_hash', 64)->nullable()->after('receipt_path')
                ->comment('sha256 del archivo de comprobante, para detectar reuso');
            $table->index('receipt_hash');
        });
    }
};
