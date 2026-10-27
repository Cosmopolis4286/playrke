<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Solicitud de retiro cripto. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md
 * secciones 14 y 16. El saldo NUNCA disminuye definitivamente antes de un
 * estado coherente: wallet_hold_id apunta al WalletHold que reserva el
 * monto (sin tocar wallets.balance) desde RESERVED hasta que
 * CryptoWithdrawalService::complete() lo captura, o cualquier camino de
 * salida (rejected/cancelled/failed) lo libera.
 *
 * crypto_transaction_id queda nullable: solo se puebla cuando el retiro
 * llega a BROADCASTED (ver CryptoGateway::broadcastWithdrawal(), Fase 5 —
 * hoy lanza CryptoCustodyNotConfiguredException, así que ningún retiro real
 * puede pasar de APPROVED todavía).
 */
return new class extends Migration
{
    public function down() : void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE crypto_withdrawals DROP CHECK chk_crypto_withdrawals_amount_positive');
        }

        Schema::dropIfExists('crypto_withdrawals');
    }

    public function up() : void
    {
        Schema::create('crypto_withdrawals', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('wallet_id')
                ->constrained('wallets')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('amount', 20, 8);
            $table->string('destination_address', 128);
            $table->string('network', 20);

            $table->foreignSnowflake('wallet_hold_id')->nullable()
                ->constrained('wallet_holds')
                ->nullOnDelete();

            $table->foreignSnowflake('crypto_transaction_id')->nullable()
                ->constrained('crypto_transactions')
                ->nullOnDelete();

            $table->enum('status', [
                'REQUESTED', 'RESERVED', 'REVIEWING', 'APPROVED', 'PROCESSING',
                'BROADCASTED', 'CONFIRMED', 'COMPLETED', 'REJECTED', 'CANCELLED', 'FAILED',
            ])->default('REQUESTED');

            $table->string('rejection_reason', 255)->nullable();
            $table->foreignSnowflake('reviewed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'user_id']);
        });

        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE crypto_withdrawals ADD CONSTRAINT chk_crypto_withdrawals_amount_positive CHECK (amount > 0)');
        }
    }
};
