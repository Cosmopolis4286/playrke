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
        Schema::create('bingo_draws_code', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del código de sorteo');
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');
            $table->tinyInteger('num')->comment('Número (1 dígito)');
            $table->char('sym', 6)->comment('Símbolo/código fijo');
            $table->timestamps();
            $table->index('created_at', 'idx_bingo_draws_code_created');

            // Evitar duplicados: un número único por sala
            $table->unique(['room_id', 'num'], 'unique_num_per_room');

            // Evitar símbolos repetidos por sala
            $table->unique(['room_id', 'sym'], 'unique_sym_per_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_draws_code');
    }
};
