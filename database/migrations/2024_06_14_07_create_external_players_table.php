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
        Schema::create('external_players', function (Blueprint $table) {
            $table->id();
            $table->string('link', 25)->unique(); // Link único del jugador externo

            // Relaciones
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->integer('room');
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('coin_id')->constrained('coins');
            $table->foreignId('seller_id')->nullable()->constrained('sellers');
            $table->foreignId('purchase_code_id')->constrained('user_purchase_codes');

            // Datos del jugador externo
            $table->integer('room_user');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('identification', 20)->nullable();
            $table->string('phone', 14);
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')
                ->comment('Estado activo/inactivo');

            $table->timestamps();

            // Índices para optimizar consultas frecuentes
            $table->index(['game_id', 'room', 'creator_user_id']);
            $table->index(['country_id', 'coin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_players');
    }
};
