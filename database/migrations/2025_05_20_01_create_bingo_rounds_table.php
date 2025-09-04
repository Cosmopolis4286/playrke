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
        Schema::create('bingo_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('bingo_rooms');
            $table->string('round', 2);
            $table->integer('start_time')->nullable();
            $table->string('last_chip', 2)->nullable();
            $table->enum('prize_type', ['PCT', 'USD', 'OTHER']);
            $table->foreignId('bingo_pattern_id')->constrained('bingo_patterns');
            $table->decimal('prize_value')->nullable();
            $table->enum('status', ['active', 'in_progress', 'finished'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rounds');
    }
};
