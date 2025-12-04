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
        Schema::create('bingo_rounds_drafts', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('bingo_room_id')->unique()->nullable()->constrained('bingo_rooms');
            $table->foreignSnowflake('automatic_bingo_room_id')->unique()->nullable()->constrained('bingo_rooms_automatic');
            $table->string('rounds', 2);
            $table->json('prize_types');
            $table->json('bingo_patterns');
            $table->json('prize_values');
            $table->enum('status', ['ACTIVE', 'EXECUTED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rounds_drafts');
    }
};
