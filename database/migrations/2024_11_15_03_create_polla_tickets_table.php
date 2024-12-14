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
        Schema::create('polla_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw')->constrained('polla_draws')->onDelete('cascade');
            $table->foreignId('player_ticket')->constrained('polla_tickets_player')->onDelete('cascade');
            $table->foreignId('coin')->constrained('coins')->onDelete('cascade');
            $table->double('exchange_rate');
            $table->double('amount');
            $table->string('n_1', 2);
            $table->string('n_2', 2);
            $table->string('n_3', 2);
            $table->string('n_4', 2);
            $table->string('n_5', 2);
            $table->string('n_6', 2);
            $table->string('n_7', 2)->nullable();
            $table->string('status', 1)->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polla_tickets');
    }
};
