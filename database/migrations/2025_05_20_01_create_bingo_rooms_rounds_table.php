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
        Schema::create('bingo_rooms_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room')->constrained('bingo_rooms');
            $table->string('round', 2)->default('1');
            $table->string('type_play', 10);
            $table->string('coin', 15)->nullable();
            $table->string('pct', 5);
            $table->double('prize_amount')->nullable();
            $table->string('status', 1)->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms_rounds');
    }
};
