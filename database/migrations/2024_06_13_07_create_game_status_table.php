<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('game_status', function (Blueprint $table) {
            $table->id();
            $table->string('value', 30)->unique();       // Código único del estado (ej: ACTIVE, FINISHED)
            $table->string('description', 100);          // Descripción legible del estado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('game_status');
    }
};
