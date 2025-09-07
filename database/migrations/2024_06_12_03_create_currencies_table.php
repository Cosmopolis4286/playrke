<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones: crear tabla 'currencies'.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            // Snowflake ID (o bigint autoincrement si prefieres)
            $table->snowflake('id')->primary();

            // Identificadores principales
            $table->string('code', 10)->unique()->comment('Código de la moneda (USD, BTC, CASINO)');
            $table->string('symbol', 12)->nullable()->comment('Símbolo de la moneda ($, €, ₿, 🎰)');
            $table->string('name', 50)->comment('Nombre corto de la moneda (Dollar, Bitcoin, Casino Token)');
            $table->string('description', 100)->nullable()->comment('Nombre completo o descripción extendida');

            // Tipo de moneda: FIAT, CRYPTO o TOKEN
            $table->enum('type', ['FIAT', 'CRYPTO', 'TOKEN'])->comment('Tipo de moneda');

            // Relación con país (opcional)
            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->onDelete('restrict')
                ->comment('Referencia al país de la moneda (si aplica)');

            // Estado activo/inactivo
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');

            $table->timestamps();
        });
    }

    /**
     * Revertir las migraciones: eliminar tabla 'currencies'.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
