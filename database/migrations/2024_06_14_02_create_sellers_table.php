<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->unsignedBigInteger('country');
            $table->unsignedBigInteger('coin');
            $table->string('phone', 14);
            $table->double('commn');
            $table->unsignedBigInteger('provider')->nullable();
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('user');
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('country')->references('id')->on('countries');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('provider')->references('id')->on('commerces')->onUpdate('cascade');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sellers');
    }
};
