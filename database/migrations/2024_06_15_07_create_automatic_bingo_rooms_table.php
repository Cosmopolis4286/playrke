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
        Schema::create('automatic_bingo_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('description', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('days')->nullable();
            $table->string('time', 10);
            $table->foreignId('game_mode')->constrained('game_modes')->onDelete('cascade');
            $table->tinyInteger('room_type')->default(1);
            $table->foreignId('jackpot_type')->constrained('jackpot_types')->onDelete('cascade')->default(1);
            $table->double('fixed_jackpot_value')->nullable();
            $table->double('ctn_price');
            $table->string('time_chip', 2)->nullable();
            $table->double('room_commn')->default(0);
            $table->double('serials_commn')->default(0);
            $table->double('pct_serials')->nullable();
            $table->double('num_serials', 2)->nullable();
            $table->foreignId('creator_type')->constrained('user_type')->onDelete('cascade');
            $table->integer('creator_id');
            $table->string('status', 1)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_bingo_rooms');
    }
};
