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
        Schema::create('polla_rke_tickets_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_rke')->onDelete('cascade');
            $table->foreignId('player_type')->constrained('player_type')->onDelete('cascade');
            $table->integer('player_id');
            $table->string('plays', 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_rke_tickets_player');
    }
};
