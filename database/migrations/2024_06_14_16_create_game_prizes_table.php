<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('description', 20);
            $table->string('code', 4);

            // Relación con la tabla 'games'
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();

            $table->boolean('status')->default(0);
            $table->timestamps();

            // Clave única compuesta: evita duplicados de código por juego
            $table->unique(['game_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_prizes');
    }
};
