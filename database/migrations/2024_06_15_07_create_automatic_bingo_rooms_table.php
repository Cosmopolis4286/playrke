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
        Schema::create('automatic_bingo_rooms', function (Blueprint $table) {
            $table->id();

            $table->string('description', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('days')->nullable();
            $table->string('time', 10);

            $table->foreignId('game_mode_id')->constrained('game_modes')->onDelete('cascade');

            $table->enum('in_live', ['Y', 'N'])->default('N');
            $table->enum('room_type', ['PV', 'PB'])->default('PV');

            $table->foreignId('prize_pool_type_id')
                ->constrained('prize_pool_types')
                ->onDelete('cascade')
                ->default(1);

            $table->decimal('amount_added_prize_pool', 12, 2)->nullable()
                ->comment('Monto agregado al pool de premios');

            $table->decimal('rke_commn', 8, 2)->default(0)->comment('Comisión RKE');
            $table->decimal('room_commn', 8, 2)->default(0)->comment('Comisión de la sala');

            $table->decimal('ctn_price', 12, 2)->comment('Precio del cartón');

            $table->json('allowed_coins')->nullable();
            $table->string('time_chip', 2)->nullable();
            $table->string('rounds', 2)->default('1');
            $table->decimal('rounds_pct', 5, 2)->nullable();
            $table->decimal('serials_pct', 5, 2)->nullable();
            $table->string('num_serials', 2)->nullable();
            $table->decimal('jackpot_pct', 5, 2)->nullable();
            $table->decimal('plays_pct', 5, 2)->nullable();
            $table->json('play_types')->nullable();

            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');

            $table->string('status', 1)->default('0');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_bingo_rooms');
    }
};
