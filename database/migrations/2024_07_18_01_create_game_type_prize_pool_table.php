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
        Schema::create('game_prize_pool_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('game_mode_id');
            $table->unsignedBigInteger('prize_pool_type_id')->default(1);
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])->default('ACTIVE');
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('games')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game_mode_id')->references('id')->on('game_modes')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('prize_pool_type_id')->references('id')->on('prize_pool_types')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_prize_pool_types');
    }
};
