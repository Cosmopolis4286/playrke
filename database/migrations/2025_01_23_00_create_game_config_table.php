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
        Schema::create('game_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game')->constrained('games')->onDelete('cascade');
            $table->string('param', 35);
            $table->string('param_valueue', 50)->nullable();
            $table->string('description', 70)->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_config');
    }
};
