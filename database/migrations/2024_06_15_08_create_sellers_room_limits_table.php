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
        Schema::create('sellers_room_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignSnowflake('game_id')->constrained('games')->onDelete('cascade');
            $table->integer('room')->comment('ID de sala');
            $table->foreignSnowflake('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->decimal('commission', 8, 2)->comment('Comisión del vendedor en esta sala');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers_room_limits');
    }
};
