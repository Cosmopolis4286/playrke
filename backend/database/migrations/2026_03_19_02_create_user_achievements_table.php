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
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('achievement_id');
            $table->timestamp('unlocked_at'); // Fecha de desbloqueo
            $table->json('progress_data')->nullable(); // Datos de progreso
            $table->boolean('is_seen')->default(false); // Si el usuario ha visto el logro
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('achievement_id')->references('id')->on('achievements')->onDelete('cascade');
            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'unlocked_at']);
            $table->index(['achievement_id', 'unlocked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
