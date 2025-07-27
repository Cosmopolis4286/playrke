<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_prize_ctns_payment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prize_ctn')->constrained('bingo_prize_ctns');
            $table->enum('payment', ['CS', 'CC', 'SP']);

            $table->foreignId('room')->constrained('bingo_rooms');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller')->constrained('sellers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('player_link')->constrained('player_links')->onDelete('cascade');
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->foreignId('coin')->constrained('coins');

            $table->double('amount', 16, 8);
            $table->string('payment_proof', 25);

            $table->timestamps();

            $table->index(['room', 'user']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_prize_ctns_payment');
    }
};
