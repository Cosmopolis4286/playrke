<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_prize_pool_types', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();

            // Foreign keys Snowflake
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreignSnowflake('game_mode_id')
                ->constrained('game_modes')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreignSnowflake('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])->default('ACTIVE');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_prize_pool_types');
    }
};
