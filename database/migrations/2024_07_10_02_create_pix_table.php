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
        Schema::create('pix', function (Blueprint $table) {
            $table->id();
            $table->string('name', 35)->nullable();
            $table->unsignedBigInteger('bank');
            $table->enum('pix_key', ['1', '2', '3', '4', '5']);
            $table->string('pix_key_value', 50);
            $table->unsignedBigInteger('user');
            $table->timestamps();

            // Foreign keys
            $table->foreign('bank')->references('id')->on('banks_brasil');
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
        Schema::dropIfExists('pixs_br');
    }
};
