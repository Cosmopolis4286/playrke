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
        Schema::create('game_extras', function (Blueprint $table) {
            $table->id();
            $table->string('description', 50);
            $table->decimal('price', 12, 2)->default(0);
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_extras');
    }
};
