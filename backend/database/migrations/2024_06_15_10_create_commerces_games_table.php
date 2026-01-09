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
        Schema::create('commerces_games', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('commerce_id')->constrained('commerces')->onDelete('cascade');
            $table->foreignSnowflake('game_id')->constrained('games')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');
            $table->softDeletes();
            $table->timestamps();

            // Evita duplicados: un comercio solo puede tener un registro por juego
            $table->unique(['commerce_id', 'game_id'], 'commerce_game_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerces_games');
    }
};
