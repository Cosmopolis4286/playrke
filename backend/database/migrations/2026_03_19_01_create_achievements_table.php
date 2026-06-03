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
        if (Schema::hasTable('achievements')) {
            return;
        }

        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // Código único del logro
            $table->string('name'); // Nombre del logro
            $table->text('description'); // Descripción del logro
            $table->string('icon')->nullable(); // Icono del logro
            $table->string('badge_color')->default('gray'); // Color de la insignia
            $table->integer('xp_reward')->default(0); // Recompensa de XP
            $table->json('requirements'); // Requisitos para desbloquear
            $table->string('category'); // Categoría del logro
            $table->boolean('is_active')->default(true); // Si está activo
            $table->boolean('is_hidden')->default(false); // Si es un logro oculto
            $table->integer('sort_order')->default(0); // Orden de visualización
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
