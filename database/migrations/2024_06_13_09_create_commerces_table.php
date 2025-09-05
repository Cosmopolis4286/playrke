<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'commerces'.
     */
    public function up(): void
    {
        Schema::create('commerces', function (Blueprint $table) {
            $table->id()->comment('ID del comercio');
            $table->string('name', 30)->comment('Nombre del comercio');
            $table->unsignedBigInteger('country_id')->comment('ID del país del comercio');
            $table->unsignedBigInteger('coin_id')->comment('ID de la moneda asociada');
            $table->string('phone', 12)->comment('Teléfono del comercio');
            $table->unsignedBigInteger('user_id')->comment('ID del usuario creador');
            $table->decimal('commn', 5, 2)->default(25)->comment('Comisión (%) por defecto');
            $table->foreignId('unique_code_id')->constrained('unique_codes');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])->default('ACTIVE')
                ->comment('Estado activo/inactivo');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('coin_id')->references('id')->on('coins')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'commerces'.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces');
    }
};
