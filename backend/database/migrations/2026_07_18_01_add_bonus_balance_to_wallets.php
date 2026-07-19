<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade bonus_balance: saldo no retirable, solo usable dentro de la app
 * (premios de juegos). Vive en la misma wallet que balance (saldo retirable).
 *
 * Los CHECK son una red de seguridad adicional (MySQL >= 8.0.16 los aplica),
 * no sustituyen la protección en aplicación — esa sigue siendo la primera
 * línea de defensa vía increment/decrement atómicos y hasSufficientBalance().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('bonus_balance', 15, 2)->default(0.00)->after('balance')
                ->comment('Saldo no retirable, solo usable dentro de la app (premios de juegos)');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_balance_nonneg CHECK (balance >= 0)');
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_bonus_balance_nonneg CHECK (bonus_balance >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets DROP CHECK chk_wallets_balance_nonneg');
            DB::statement('ALTER TABLE wallets DROP CHECK chk_wallets_bonus_balance_nonneg');
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('bonus_balance');
        });
    }
};
