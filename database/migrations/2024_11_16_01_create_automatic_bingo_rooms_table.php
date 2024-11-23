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
            $table->string('time', 10);
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('jackpot_type')->default(1);
            $table->double('fixed_jackpot_value')->nullable();
            $table->double('jackpot')->default(0);
            $table->double('ctn_price');
            $table->double('room_commn')->default(0);
            $table->double('serials_commn')->default(0);
            $table->double('val_serials')->nullable();
            $table->double('num_serials', 2)->nullable();
            $table->foreignId('creator_type')->constrained('user_type')->onDelete('cascade');
            $table->integer('creator_id');
            $table->boolean('status')->default(0);
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
