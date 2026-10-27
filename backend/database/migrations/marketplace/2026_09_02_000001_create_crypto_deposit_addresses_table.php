<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Dirección de depósito derivada asignada a un usuario. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 10-11 y 21: la aplicación NUNCA
 * calcula `address` a partir de una master key — derivation_index/address
 * son devueltos por un Signer/Custody Layer externo (CryptoGateway::deriveAddress()),
 * esta tabla solo los persiste.
 *
 * FASE 5 (esqueleto, sin custodia real): en este estado, address se genera
 * de forma determinística por un provider STUB (TronUsdtProvider) — nunca es
 * una dirección real capaz de recibir fondos. No usar en producción hasta
 * que exista un proveedor de custodia real detrás de CryptoGateway.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('crypto_deposit_addresses');
    }

    public function up() : void
    {
        Schema::create('crypto_deposit_addresses', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('network', 20)->comment('TRON | ETHEREUM | POLYGON — abstracción desde el día 1, ver CryptoGateway');

            $table->unsignedBigInteger('derivation_index')->unique()
                ->comment('Índice secuencial global de derivación, nunca reutilizado');

            $table->string('address', 64)->unique();

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            $table->unique(['user_id', 'currency_id', 'network'], 'uniq_deposit_address_user_currency_network');
            $table->index('status');
        });
    }
};
