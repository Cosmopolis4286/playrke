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
            $table->unsignedBigInteger('game');
            $table->string('move', 8); // Bet; Rollback 
            $table->unsignedBigInteger('user');
            $table->unsignedBigInteger('provider');
            $table->timestamps();

            $table->foreign('user')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('provider')->references('id')->on('providers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game')->references('id')->on('games')->onDelete('restrict')->onUpdate('cascade');
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
