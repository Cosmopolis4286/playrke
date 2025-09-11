<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prize_pool_types', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID del tipo de premio en el pool');
            $table->string('description', 50)->comment('Descripción legible del tipo de premio');
            $table->boolean('is_active')->default(true)
                ->comment('true = activo, false = inactivo'); // útil para filtrar tipos obsoletos
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prize_pool_types');
    }
};
