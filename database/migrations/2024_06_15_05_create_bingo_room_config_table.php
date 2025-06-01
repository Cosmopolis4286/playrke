<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBingoRoomConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bingo_rooms_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room');
            $table->enum('creation', ['M', 'A'])->default('M');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('game_mode');
            $table->enum('in_live', ['Y', 'N'])->default('N');
            $table->date('date');
            $table->string('time', 10);
            $table->integer('unix_date');
            $table->unsignedBigInteger('country')->nullable();
            $table->string('coin', 15)->nullable();
            $table->double('ctn_price');
            $table->double('rke_commn')->nullable();
            $table->double('room_commn')->nullable();
            $table->unsignedBigInteger('prize_pool_type')->default(1);
            $table->double('amount_added_prize_pool')->nullable();
            $table->string('time_chip', 2)->nullable();
            $table->string('rounds', 2)->default('1');
            $table->double('rounds_pct')->nullable();
            $table->double('serials_pct')->nullable();
            $table->string('num_serials', 2)->nullable();
            $table->double('jackpot_pct')->nullable();
            $table->double('plays_pct')->nullable();
            $table->json('play_types')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('room')->references('id')->on('bingo_rooms')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game_mode')->references('id')->on('game_modes')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('country')->references('id')->on('countries')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('prize_pool_type')->references('id')->on('prize_pool_types')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_room_config');
    }
}
