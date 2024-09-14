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
            $table->double('commerce_commn')->default(0);
            $table->double('seller_commn')->default(0);
            $table->double('jackpot')->default(0);
            $table->double('jackpot_prize')->default(0);
            $table->double('serial_prize')->default(0);
            $table->integer('ctns')->default(0);
            $table->integer('close_room')->nullable();
            $table->string('encrypted', 18)->unique();
            $table->string('code_uuid', 36)->nullable()->unique();
            $table->timestamps();

            // Definición de la clave foránea para 'status'
            $table->foreign('game_mode')->references('id')->on('game_modes');

            // Definición de la clave foránea para 'status'
            $table->foreign('status')->references('id')->on('game_status');

            // Definición de la clave foránea para 'customer'
            $table->foreign('customer')->references('id')->on('customers');

            // Definición de la clave foránea para 'commerce'
            $table->foreign('commerce')->references('id')->on('commerces');

            // Definición de la clave foránea para 'subsidiary'
            $table->foreign('subsidiary')->references('id')->on('subsidiaries');

            // Definición de la clave foránea para 'country'
            $table->foreign('country')->references('id')->on('countries');

            // Definición de la clave foránea para 'coin'
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
        Schema::dropIfExists('bingo_rooms');
    }
};
