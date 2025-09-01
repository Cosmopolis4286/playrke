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
        Schema::create('bingo_creators', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');

            // Datos del creador
            $table->unsignedBigInteger('creator_id');
            $table->double('creator_commn', 15, 2)->default(0);
            $table->double('ctn_price', 15, 2);
            $table->json('allowed_coins')->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();

            // Índices adicionales opcionales
            $table->index(['room_id', 'creator_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_creators');
    }
};
