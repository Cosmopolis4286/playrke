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
        Schema::create('bingo_rooms_user_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('room_user_id')->constrained('bingo_rooms_user')->onDelete('cascade');
            $table->foreignId('round_id')->constrained('bingo_rounds');
            $table->enum('award_status', ['waiting', 'paid'])->default('waiting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms_user_winners');
    }
};
