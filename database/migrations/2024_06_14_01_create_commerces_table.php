<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommercesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commerces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('code', 10)->nullable()->unique();
            $table->unsignedBigInteger('country');
            $table->unsignedBigInteger('coin');
            $table->string('phone', 12);
            $table->unsignedBigInteger('user');
            $table->double('commn')->default(25);
            $table->boolean('all_in')->default(0);
            $table->string('code_rke', 16)->nullable();
            $table->string('code_uuid', 36);
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('country')->references('id')->on('countries');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commerces');
    }
}
