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
        Schema::create('trivia_rooms_multi', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('time', 10);
            $table->integer('unix_date')->nullable();
            $table->unsignedBigInteger('status')->default(1);
            $table->double('price');
            $table->integer('players')->default(0);
            $table->double('all_sale')->default(0);
            $table->double('jackpot')->default(0);
            $table->double('room_commn')->default(0);
            $table->integer('close_room')->nullable();
            $table->string('advertising_image', 30);
            $table->string('code_uuid', 36)->nullable()->unique();
            $table->timestamps();

            $table->foreign('status')->references('id')->on('game_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trivia_rooms_multi');
    }
};
