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
        Schema::create('polla_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_draws')->onDelete('cascade');
            $table->enum('player_type', ['USER', 'LINK']);
            $table->integer('player_id');
            $table->string('player_name', 10)->nullable();
            $table->string('player_lastname', 15)->nullable();
            $table->string('identification_doc', 12)->nullable();
            $table->string('plays', 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_players');
    }
};
