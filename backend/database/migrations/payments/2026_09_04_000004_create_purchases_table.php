<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Ledger de "qué se entregó a quién y por qué pago" — separado de
 * wallet_movements y de crypto_transactions (ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 4).
 *
 * payment_order_id UNIQUE es la defensa estructural contra doble-entrega:
 * PurchaseService::deliver() nunca puede crear dos Purchase para la misma
 * orden, sin depender de que la lógica de aplicación lo recuerde.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('purchases');
    }

    public function up() : void
    {
        Schema::create('purchases', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignSnowflake('payment_order_id')
                ->unique()
                ->constrained('payment_orders')
                ->restrictOnDelete();

            $table->foreignSnowflake('payment_product_id')
                ->constrained('payment_products')
                ->restrictOnDelete();

            $table->foreignSnowflake('player_asset_id')
                ->constrained('player_assets')
                ->restrictOnDelete();

            $table->timestamp('delivered_at');

            $table->timestamps();

            $table->index('user_id');
        });
    }
};
