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
        Schema::create('game_sounds', function (Blueprint $table) {
            $table->id();
            $table->string('file', 35)->unique()->comment('Nombre del archivo de sonido');
            $table->string('description', 50)->comment('Descripción del sonido');
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sounds');
    }
};
