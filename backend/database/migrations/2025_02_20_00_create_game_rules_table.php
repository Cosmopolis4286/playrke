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
        Schema::create('game_rules', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignSnowflake('game_id')->constrained('games')->onDelete('cascade');
            $table->string('image', 30)->nullable();
            $table->text('rules');
            $table->string('encrypted', 12);
            $table->boolean('status')->default(true)->comment('true = activo, false = inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rules');
    }
};
