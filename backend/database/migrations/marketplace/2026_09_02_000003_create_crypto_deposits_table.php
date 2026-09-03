<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa "la blockchain confirmó" (crypto_transactions.status=CONFIRMED) de
 * "el ledger interno acreditó" (wallet_movement_id poblado) — pueden ocurrir
 * en momentos distintos si el acreditado es asíncrono. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 22.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_deposits', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('crypto_transaction_id')->unique()
                ->constrained('crypto_transactions')
                ->cascadeOnDelete();

            $table->foreignSnowflake('wallet_movement_id')->nullable()
                ->constrained('wallet_movements')
                ->nullOnDelete()
                ->comment('Poblado solo cuando el crédito interno ya se aplicó');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_deposits');
    }
};
