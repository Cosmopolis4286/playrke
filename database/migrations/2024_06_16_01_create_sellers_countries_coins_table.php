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
        Schema::create('sellers_countries_coins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller');
            $table->unsignedBigInteger('country')->nullable();
            $table->unsignedBigInteger('coin');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('seller')->references('id')->on('sellers');
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
        Schema::dropIfExists('sellers_countries_coins');
    }
};
