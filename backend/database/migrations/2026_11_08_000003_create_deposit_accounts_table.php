<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Cuentas de la plataforma donde el jugador deposita (pago móvil,
 * transferencia, Zelle, Binance…), por moneda. Antes el jugador no sabía a
 * qué cuenta pagar: el formulario solo pedía el comprobante.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('deposit_accounts');
    }

    public function up() : void
    {
        if (Schema::hasTable('deposit_accounts')) {
            return;
        }

        Schema::create('deposit_accounts', function(Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('currency_id')->constrained('currencies')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('method', 30)->comment('pago_movil, transferencia, zelle, binance, efectivo, otro');
            $table->string('label', 80)->comment('Nombre visible, ej. "Banesco — Pago móvil"');
            $table->string('bank_name', 80)->nullable();
            $table->string('holder_name', 120);
            $table->string('holder_document', 40)->nullable()->comment('Cédula / RIF del titular');
            $table->string('account_identifier', 120)->comment('Número de cuenta, teléfono, correo o ID de pago');
            $table->string('instructions', 500)->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['currency_id', 'status']);
        });
    }
};
