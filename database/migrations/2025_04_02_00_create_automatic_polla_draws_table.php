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
        Schema::create('automatic_polla_draws', function (Blueprint $table) {
            $table->id();
            $table->string('description', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('days')->nullable();
            $table->foreignId('game_type')->constrained('polla_types')->onDelete('cascade');
            $table->foreignId('game_mode')->constrained('game_modes')->onDelete('cascade');
            $table->enum('room_type', ['PV', 'PB']);
            $table->foreignId('jackpot_type')->constrained('jackpot_types')->onDelete('cascade')->default(1);
            $table->double('fixed_jackpot_value')->nullable();
            $table->double('room_commn')->default(0);
            $table->double('ticket_price');
            $table->double('pct_serials')->nullable();
            $table->double('num_serials', 2)->nullable();
            $table->double('serials_commn')->nullable();
            $table->foreignId('creator_type')->constrained('user_type')->onDelete('cascade');
            $table->integer('creator_id');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->string('status', 1)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_polla_draws');
    }
};
