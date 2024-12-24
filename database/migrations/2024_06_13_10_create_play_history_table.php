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
        Schema::create('play_history', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('time', 10);
            $table->foreignId('game')->constrained('games')->onDelete('cascade');
            $table->integer('room');
            $table->string('coin', 8);
            $table->double('gain');
            $table->string('action', 15);
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_history');
    }
};
