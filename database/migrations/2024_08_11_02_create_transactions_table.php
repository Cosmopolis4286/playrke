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
            $table->unsignedBigInteger('customer');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game');
            $table->unsignedBigInteger('game_mode');
            $table->string('bet_id');
            $table->string('code')->unique();
            $table->string('transaction_type', 12);
            $table->string('coin', 15);
            $table->double('amount');
            $table->boolean('status')->default(0);
            $table->timestamp('processing_data')->nullable();
            $table->timestamps();

            $table->foreign('customer')->references('id')->on('customers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('customers_users')->onDelete('restrict')->onUpdate('cascade');
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
