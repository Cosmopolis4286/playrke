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
            $table->foreignSnowflake('room_id')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignSnowflake('room_user_id')->constrained('bingo_room_users')->onDelete('cascade');
            $table->foreignSnowflake('round_id')->constrained('bingo_rounds');
            $table->enum('award_status', ['WAITING', 'PAID'])->default('WAITING');
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
