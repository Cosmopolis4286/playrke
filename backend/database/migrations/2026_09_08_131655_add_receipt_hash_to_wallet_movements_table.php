<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: agregar receipt_hash a wallet_movements.
     *
     * sha256 del archivo de comprobante subido en un depósito — permite al
     * panel admin (WalletMovementController) detectar reuso del mismo
     * comprobante (captura de pantalla) en otro depósito, la señal de
     * fraude más común en pagos con comprobante manual.
     */
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->char('receipt_hash', 64)->nullable()->after('receipt_path')
                ->comment('sha256 del archivo de comprobante, para detectar reuso');
            $table->index('receipt_hash');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropIndex(['receipt_hash']);
            $table->dropColumn('receipt_hash');
        });
    }
};
