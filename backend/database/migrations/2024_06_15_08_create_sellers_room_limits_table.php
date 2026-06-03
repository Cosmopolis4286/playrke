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
        if (Schema::hasTable('sellers_room_limits')) {
            return;
        }

        Schema::create('sellers_room_limits', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('game_id')->constrained('games')->onDelete('cascade');
            $table->string('room_id')->comment('ID de sala');
            $table->foreignSnowflake('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->decimal('commission', 8, 2)->comment('Comisión del vendedor en esta sala');
            $table->timestamps();

            $table->unique(['game_id', 'room_id', 'seller_id'], 'unique_seller_room_limit');
            $table->index(['game_id', 'room_id'], 'idx_seller_room_limit_game_room');
            $table->index('seller_id', 'idx_seller_room_limit_seller');
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
