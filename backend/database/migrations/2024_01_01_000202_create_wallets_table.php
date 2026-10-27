<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'wallets'.
     */
    public function down() : void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets DROP CHECK chk_wallets_balance_nonneg');
            DB::statement('ALTER TABLE wallets DROP CHECK chk_wallets_bonus_balance_nonneg');
        }

        Schema::dropIfExists('wallets');
    }

    /**
     * Ejecutar la migración: crear tabla 'wallets'.
     */
    public function up() : void
    {
        Schema::create('wallets', function(Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la wallet');

            // Relación con usuario usando Snowflake
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del usuario dueño de la wallet');

            // Relación con moneda usando Snowflake
            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de la moneda asociada');

            // Balance de la wallet
            $table->decimal('balance', 15, 2)
                ->default(0.00)
                ->comment('Balance en moneda local');

            // Saldo no retirable, solo usable dentro de la app (premios de juegos)
            $table->decimal('bonus_balance', 15, 2)->default(0.00)
                ->comment('Saldo no retirable, solo usable dentro de la app (premios de juegos)');

            // Deuda pendiente de cobro — ver WalletService::settlePendingDebt()
            // y migración 2026_08_31_000100_add_pending_debt_to_wallets_table
            // para el porqué (reversión de premio en disputa que excedió el
            // saldo disponible al momento de revertir).
            $table->decimal('pending_debt', 15, 2)->default(0.00)
                ->comment('Deuda pendiente de cobro — se descuenta automáticamente del próximo crédito a la wallet');

            // Estado de la wallet
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE');

            // Timestamps automáticos
            $table->timestamps();

            // Índice único compuesto: un usuario solo puede tener una wallet por moneda.
            // Sirve también como índice para consultas frecuentes por usuario y moneda.
            $table->unique(['user_id', 'currency_id']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_balance_nonneg CHECK (balance >= 0)');
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_bonus_balance_nonneg CHECK (bonus_balance >= 0)');
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallets_pending_debt_nonneg CHECK (pending_debt >= 0)');
        }
    }
};
