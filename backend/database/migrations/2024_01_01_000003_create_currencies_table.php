<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('currencies');
    }

    public function up() : void
    {
        Schema::create('currencies', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('iso_code', 10)
                ->unique()
                ->comment('Código único de la moneda (USD, BTC, CASINO)');

            $table->string('symbol', 12)
                ->nullable()
                ->comment('Símbolo de la moneda ($, €, ₿, 🎰)');

            $table->string('name', 50)
                ->comment('Nombre corto de la moneda (Dollar, Bitcoin, Casino Token)');

            $table->string('description', 255)
                ->nullable()
                ->comment('Descripción extendida o nombre completo');

            $table->enum('type', ['FIAT', 'CRYPTO', 'TOKEN'])
                ->comment('Tipo de moneda');

            $table->foreignSnowflake('country_id')
                ->nullable()
                ->constrained('countries')
                ->onDelete('restrict')
                ->comment('Referencia al país de la moneda (si aplica)');

            // Índice para optimizar búsquedas por país
            $table->index('country_id');

            $table->tinyInteger('decimal_places')
                ->default(2)
                ->unsigned()
                ->comment('Cantidad de decimales');

            $table->char('decimal_separator', 1)
                ->default(',')
                ->comment('Carácter usado como separador decimal (ej: , para es-VE, . para en-US)');

            $table->char('thousands_separator', 1)
                ->default('.')
                ->comment('Carácter usado como separador de miles (ej: . para es-VE, , para en-US)');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE');

            $table->timestamps();

            // Índice compuesto para búsquedas frecuentes por tipo y estado
            $table->index(['type', 'status']);
        });
    }
};
