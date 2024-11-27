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
        Schema::create('polla_draw_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_draws')->onDelete('cascade'); 
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('status')->default(1);
            $table->tinyInteger('room_type')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_draw_config');
    }
};
