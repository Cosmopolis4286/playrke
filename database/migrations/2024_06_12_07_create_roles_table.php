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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();         // Nombre del rol
            $table->string('prefix')->unique();       // Prefijo para identificar rol en el sistema
            $table->string('description')->nullable(); // Descripción opcional
            $table->enum('status', ['active', 'inactive'])->default('active');    // Estado activo/inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
