<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones: crear tabla 'coins'.
     */
    public function up(): void
    {
        Schema::create('coins', function (Blueprint $table) {
            // Snowflake ID
            $table->snowflake('id')->primary();

            // Campos legibles y únicos
            $table->string('iso_code', 4)->unique()->comment('Código ISO de la moneda');
            $table->string('symbol', 6)->comment('Símbolo de la moneda');
            $table->string('short_name', 10)->comment('Nombre corto de la moneda');
            $table->string('long_name', 15)->comment('Nombre largo de la moneda');

            // Relación con país (opcional)
            $table->foreignSnowflake('country')->nullable()->constrained('countries')->onDelete('restrict')
                ->comment('Referencia al país de la moneda');

            // Estado activo/inactivo
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');

            $table->timestamps();
        });
    }

    /**
     * Revertir las migraciones: eliminar tabla 'coins'.
     */
    public function down(): void
    {
        Schema::dropIfExists('coins');
    }
};
