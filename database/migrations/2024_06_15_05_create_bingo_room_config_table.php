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
        Schema::create('bingo_rooms_config', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->enum('creation', ['M', 'A'])->default('M');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('game_mode_id')
                ->constrained('game_modes')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->enum('in_live', ['Y', 'N'])->default('N');
            $table->date('date');
            $table->string('time', 10);
            $table->integer('unix_date');

            $table->decimal('ctn_price', 12, 2);
            $table->json('allowed_coins')->nullable();
            $table->decimal('rke_commn', 8, 2)->nullable();
            $table->decimal('room_commn', 8, 2)->nullable();

            $table->foreignId('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->default(1)
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable();
            $table->string('time_chip', 2)->nullable();
            $table->string('rounds', 2)->default('1');
            $table->decimal('rounds_pct', 5, 2)->nullable();
            $table->decimal('serials_pct', 5, 2)->nullable();
            $table->string('num_serials', 2)->nullable();
            $table->decimal('jackpot_pct', 5, 2)->nullable();
            $table->decimal('plays_pct', 5, 2)->nullable();
            $table->json('play_types')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms_config');
    }
};
