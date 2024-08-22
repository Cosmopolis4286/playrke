<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('subsidiary')->nullable();
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('game_mode');
            $table->unsignedBigInteger('user_id');
            $table->string('action', 12);
            $table->double('amount');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('commerce')->references('id')->on('commerces')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('subsidiary')->references('id')->on('subsidiaries');
            $table->foreign('user_id')->references('id')->on('users_commerces')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game')->references('id')->on('games')->onDelete('restrict')->onUpdate('cascade');
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
        Schema::dropIfExists('transactions');
    }
}
