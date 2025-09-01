<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_prize_ctns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room')->constrained('bingo_rooms');
            $table->string('round', 2);
            $table->enum('active', ['Y', 'N'])->default('N');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller')->nullable()->constrained('sellers');
            $table->foreignId('room_user')->constrained('bingo_rooms_user');
            $table->foreignId('lot')->constrained('bingo_ctns_lot');
            $table->foreignId('ctn')->constrained('bingo_ctns');
            $table->enum('prize_type', ['FLHE', 'SONG', 'SRL']);
            $table->string('pattern', 50);
            $table->double('usd_amount', 10, 2);
            $table->string('coin', 15);
            $table->double('amount', 16, 8);
            $table->enum('status', ['WT', 'CC', 'CS', 'SP']);
            $table->timestamps();
            $table->index('room'); // índice ya existente
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_prize_ctns');
    }
};
