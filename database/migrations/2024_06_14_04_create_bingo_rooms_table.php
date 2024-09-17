<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bingo_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('customer')->nullable();
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('subsidiary')->nullable();
            $table->unsignedBigInteger('status')->default(1);
            $table->boolean('all_for_one')->default(0);
            $table->boolean('paid_win')->default(0);
            $table->date('date');
            $table->string('time', 10);
            $table->integer('unix_time')->nullable();
            $table->unsignedBigInteger('country')->nullable();
            $table->unsignedBigInteger('coin')->nullable();
            $table->timestamp('publication_date')->nullable();
            $table->double('ctn_price');
            $table->integer('players')->default(0);
            $table->double('all_sale')->default(0);
            $table->double('room_commn')->default(0);
            $table->double('seller_commn')->default(0);
            $table->double('jackpot')->default(0);
            $table->double('jackpot_prize')->default(0);
            $table->double('serial_prize')->default(0);
            $table->integer('ctns')->default(0);
            $table->integer('close_room')->nullable();
            $table->string('encrypted', 18)->unique();
            $table->string('code_uuid', 36)->nullable()->unique();
            $table->timestamps();

            // Definición de las claves foráneas
            $table->foreign('game_mode')->references('id')->on('game_modes');
            $table->foreign('status')->references('id')->on('game_status');
            $table->foreign('customer')->references('id')->on('customers');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('subsidiary')->references('id')->on('subsidiaries');
            $table->foreign('country')->references('id')->on('countries');
            $table->foreign('coin')->references('id')->on('coins');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            // Eliminar claves foráneas antes de eliminar la tabla
            $table->dropForeign(['game_mode']);
            $table->dropForeign(['status']);
            $table->dropForeign(['customer']);
            $table->dropForeign(['commerce']);
            $table->dropForeign(['subsidiary']);
            $table->dropForeign(['country']);
            $table->dropForeign(['coin']);
        });

        Schema::dropIfExists('bingo_rooms');
    }
};
