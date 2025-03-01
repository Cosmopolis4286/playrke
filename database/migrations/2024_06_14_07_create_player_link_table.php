<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayerLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_link', function (Blueprint $table) {
            $table->id();
            $table->string('link', 25);
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('room');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('country');
            $table->unsignedBigInteger('coin');
            $table->unsignedBigInteger('seller')->nullable();
            $table->unsignedBigInteger('user')->nullable();
            $table->string('name', 20);
            $table->string('lastname', 20);
            $table->string('identification', 12)->nullable();
            $table->string('phone', 14);
            $table->unsignedBigInteger('purchase_code');
            $table->timestamps();

            // Foreign keys
            $table->foreign('country')->references('id')->on('countries');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('game')->references('id')->on('games');
            $table->foreign('room')->references('id')->on('bingo_rooms');
            $table->foreign('seller')->references('id')->on('sellers');
            $table->foreign('user')->references('id')->on('users');
            $table->foreign('purchase_code')->references('id')->on('user_purchase_codes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('player_link');
    }
};
