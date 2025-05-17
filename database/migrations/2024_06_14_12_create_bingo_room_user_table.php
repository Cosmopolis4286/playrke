<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bingo_rooms_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade')->nullable();
            $table->integer('player_user')->nullable();
            $table->string('ctns', 3);
            $table->double('automarker')->nullable();
            $table->string('winner', 1)->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bingo_rooms_user');
    }
};
