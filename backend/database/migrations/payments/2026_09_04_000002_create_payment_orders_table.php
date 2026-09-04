<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de compra de un PaymentProduct pagada en USDT-TRC20. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md secciones 4 y 6.
 *
 * crypto_transaction_id UNIQUE: una transacción on-chain nunca se asocia a
 * más de una orden — segunda capa de defensa contra doble-acreditación,
 * además del tx_hash UNIQUE ya existente en crypto_transactions.
 *
 * token_contract es un snapshot al crear la orden (no una FK a config) para
 * que un cambio futuro de contrato configurado no reinterprete órdenes
 * viejas ya emitidas.
 *
 * deposit_address/derivation_index son PROPIOS de esta orden (no reutilizan
 * crypto_deposit_addresses, cuyo unique(user_id, currency_id, network) exige
 * una sola dirección activa por usuario+red — aquí, en cambio, cada orden
 * necesita su PROPIA dirección efímera para poder identificar el pago solo
 * por dirección de destino, sin depender de que el monto coincida exacto
 * cuando dos órdenes del mismo usuario están abiertas a la vez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignSnowflake('payment_product_id')
                ->constrained('payment_products')
                ->restrictOnDelete();

            $table->string('network', 20)->default('TRON');
            $table->string('token', 20)->default('USDT');
            $table->string('token_contract', 64);

            $table->unsignedBigInteger('derivation_index')->unique();
            $table->string('deposit_address', 64);

            $table->decimal('expected_amount', 20, 8);
            $table->decimal('received_amount', 20, 8)->nullable();

            $table->foreignSnowflake('crypto_transaction_id')->nullable()
                ->unique()
                ->constrained('crypto_transactions')
                ->nullOnDelete();

            $table->enum('status', [
                'PENDING', 'DETECTED', 'CONFIRMING', 'CONFIRMED',
                'UNDERPAID', 'OVERPAID', 'EXPIRED', 'FAILED', 'CANCELLED', 'CREDITED',
            ])->default('PENDING');

            $table->timestamp('detected_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at');

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('deposit_address');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
