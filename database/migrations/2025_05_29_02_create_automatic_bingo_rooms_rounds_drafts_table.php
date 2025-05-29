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
        Schema::create('automatic_bingo_rooms_rounds_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automatic_bingo_id')->constrained('automatic_bingo_rooms')->onDelete('cascade');
            $table->string('rounds', 2);
            $table->json('prize_types');
            $table->json('play_types');
            $table->json('prize_values');
            $table->enum('status', ['ACTIVE', 'COMPLETED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_bingo_rooms_rounds_drafts');
    }
};
