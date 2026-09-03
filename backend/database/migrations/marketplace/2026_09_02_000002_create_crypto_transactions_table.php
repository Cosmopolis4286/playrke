<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger cripto — separado de wallet_movements (ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 15 y 23). La blockchain es la
 * fuente de verdad para "existe esta tx / cuántas confirmaciones tiene /
 * cuánto se envió" — esta tabla solo persiste esa información ya observada,
 * nunca la infiere.
 *
 * tx_hash UNIQUE es la defensa real contra doble-procesamiento de un mismo
 * depósito — igual rol que wallet_movements.idempotency_key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('tx_hash', 100)->unique();
            $table->string('network', 20);
            $table->string('token', 20)->comment('Símbolo del token, ej. USDT');

            $table->enum('direction', ['DEPOSIT', 'WITHDRAWAL']);

            $table->string('from_address', 128);
            $table->string('to_address', 128);

            $table->decimal('amount', 30, 8)->comment('Precisión on-chain, distinta de decimal(15,2) fiat');

            $table->enum('status', ['PENDING', 'CONFIRMED', 'CREDITED', 'FAILED'])->default('PENDING');
            $table->unsignedInteger('confirmations')->default(0);

            $table->foreignSnowflake('user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Resuelto vía to_address -> crypto_deposit_addresses -> user, null si no matchea ningún usuario');

            $table->foreignSnowflake('wallet_id')->nullable()
                ->constrained('wallets')
                ->nullOnDelete();

            $table->timestamps();
            $table->timestamp('confirmed_at')->nullable();

            $table->index(['status', 'direction']);
            $table->index('user_id');
            $table->index('to_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_transactions');
    }
};
