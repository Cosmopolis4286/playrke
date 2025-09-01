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
            $table->enum('in_live', ['Y', 'N'])->default('N');
            $table->enum('room_type', ['PV', 'PB'])->default('PV');
            $table->foreignId('prize_pool_type')->constrained('prize_pool_types')->onDelete('cascade')->default(1);
            $table->double('amount_added_prize_pool')->nullable();
            $table->double('rke_commn')->default(0);
            $table->double('room_commn')->default(0);
            $table->double('ctn_price');
            $table->json('allowed_coins')->nullable();
            $table->string('time_chip', 2)->nullable();
            $table->string('rounds', 2)->default('1');
            $table->double('rounds_pct')->nullable();
            $table->double('serials_pct')->nullable();
            $table->string('num_serials', 2)->nullable();
            $table->double('jackpot_pct')->nullable();
            $table->double('plays_pct')->nullable();
            $table->json('play_types')->nullable();
            $table->foreignId('creator_type')->constrained('user_type')->onDelete('cascade');
            $table->integer('creator_id');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->string('status', 1)->default('0');
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
