<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'countries'.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id()->comment('ID del país');
            $table->string('name', 35)->unique()->comment('Nombre del país');
            $table->string('iso_code_2', 2)->unique()->comment('Código ISO de 2 letras');
            $table->string('iso_code_3', 3)->unique()->comment('Código ISO de 3 letras');
            $table->boolean('status')->default(false)->comment('Estado activo/inactivo');
            $table->timestamps();
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'countries'.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
