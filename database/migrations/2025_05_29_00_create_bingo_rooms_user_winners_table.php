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
            $table->foreignId('room')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('room_user')->constrained('bingo_rooms_user')->onDelete('cascade');
            $table->foreignId('round')->constrained('bingo_rooms_rounds');
            $table->enum('status', ['Y', 'N'])->default('N');
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
