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
        Schema::create('trivia_rooms_multi_quest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room')->constrained('bingo_rooms')->onDelete('cascade');
            $table->string('num_quest', 2);
            $table->string('imagen', 30)->nullable();
            $table->string('question', 60);
            $table->string('answer', 30);
            $table->string('option_1', 30);
            $table->string('option_2', 30);
            $table->string('option_3', 30);
            $table->string('option_4', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trivia_rooms_multi_quest');
    }
};
