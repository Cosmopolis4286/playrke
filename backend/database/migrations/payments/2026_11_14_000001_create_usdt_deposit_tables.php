<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Depósitos USDT-TRC20 a la billetera con "una dirección + monto único"
 * (config payments.usdt_deposits). Dos tablas:
 *
 * - usdt_deposit_orders: lo que el jugador pidió depositar. amount_units es
 *   el monto único en micro-USDT (6 decimales, entero): es la llave con la
 *   que el watcher reconoce el pago.
 * - usdt_inbound_transfers: cada transferencia TRC20 observada en la
 *   dirección receptora, una vez (tx_hash único). Las que no coinciden con
 *   ninguna orden quedan UNMATCHED para tesorería.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('usdt_inbound_transfers');
        Schema::dropIfExists('usdt_deposit_orders');
    }

    public function up() : void
    {
        Schema::create('usdt_deposit_orders', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('user_id', 20)->index();
            $table->string('wallet_id', 20);
            $table->decimal('requested_amount', 20, 2);
            $table->unsignedBigInteger('amount_units')->comment('Monto único a pagar, en micro-USDT');
            $table->string('deposit_address', 64);
            $table->string('status', 20)->index();
            $table->string('tx_hash', 100)->nullable()->unique();
            $table->string('from_address', 64)->nullable();
            $table->decimal('received_amount', 20, 6)->nullable();
            $table->decimal('credited_amount', 20, 2)->nullable();
            $table->string('wallet_movement_id', 20)->nullable();
            $table->string('review_reason', 255)->nullable();
            $table->string('reviewed_by', 20)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['amount_units', 'status']);
        });

        Schema::create('usdt_inbound_transfers', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('tx_hash', 100)->unique();
            $table->string('from_address', 64);
            $table->string('to_address', 64);
            $table->decimal('amount', 20, 6);
            $table->unsignedBigInteger('amount_units');
            $table->timestamp('block_time');
            $table->string('status', 20)->index()->comment('MATCHED | LATE | UNMATCHED | IGNORED');
            $table->string('usdt_deposit_order_id', 20)->nullable()->index();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }
};
