<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('iso_code', 10)->unique()->comment('Código de la moneda (USD, BTC, CASINO)');
            $table->string('symbol', 12)->nullable()->comment('Símbolo de la moneda ($, €, ₿, 🎰)');
            $table->string('name', 50)->comment('Nombre corto de la moneda (Dollar, Bitcoin, Casino Token)');
            $table->string('description', 100)->nullable()->comment('Nombre completo o descripción extendida');

            $table->enum('type', ['FIAT', 'CRYPTO', 'TOKEN'])->comment('Tipo de moneda');

            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->onDelete('restrict')
                ->comment('Referencia al país de la moneda (si aplica)');

            $table->tinyInteger('decimal_places')->default(2)->comment('Cantidad de decimales');

            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
