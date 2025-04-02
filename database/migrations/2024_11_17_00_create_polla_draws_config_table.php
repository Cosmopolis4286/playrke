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
        Schema::create('polla_draws_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_draws')->onDelete('cascade');
            $table->enum('creation', ['M', 'A'])->default('M');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('game_mode')->constrained('game_modes')->onDelete('cascade');
            $table->double('ticket_price')->default(0);
            $table->double('room_commn')->nullable();
            $table->unsignedBigInteger('jackpot_type')->default(1);
            $table->double('fixed_jackpot_value')->nullable();
            $table->string('active_prizes', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_draws_config');
    }
};
