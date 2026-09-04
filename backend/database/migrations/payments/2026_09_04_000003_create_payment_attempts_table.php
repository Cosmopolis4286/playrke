<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log append-only de cada evaluación que el watcher hace sobre una
 * PaymentOrder (detectar / confirmar / acreditar) — trazabilidad fina para
 * soporte/auditoría, nunca se lee para tomar decisiones de negocio (eso lo
 * hace PaymentOrder.status). Ver PAYMENTS_USDT_TRC20_DESIGN.md sección 4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('payment_order_id')
                ->constrained('payment_orders')
                ->cascadeOnDelete();

            $table->string('tx_hash', 100)->nullable();
            $table->string('attempted_action', 40);
            $table->enum('result', ['SUCCESS', 'REJECTED', 'ERROR']);
            $table->string('reason', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_order_id');
            $table->index('tx_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
