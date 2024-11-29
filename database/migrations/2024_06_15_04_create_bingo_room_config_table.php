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
            $table->unsignedBigInteger('game_mode');
            $table->enum('creation', ['M', 'A'])->default('M');
            $table->tinyInteger('room_type')->default(1);
            $table->unsignedBigInteger('jackpot_type')->default(1);
            $table->double('fixed_jackpot_value')->nullable();
            $table->boolean('replay')->default(0);
            $table->double('room_commn')->default(10);
            $table->double('serials_commn')->nullable();
            $table->double('val_serials')->nullable();
            $table->string('num_serials', 2)->nullable();
            $table->double('val_sm_prizes')->nullable();
            $table->string('play_types', 25)->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('room')->references('id')->on('bingo_rooms')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('jackpot_type')->references('id')->on('jackpot_types')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game_mode')->references('id')->on('game_modes');
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
