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
            $table->unsignedBigInteger('bank');
            $table->unsignedBigInteger('pix_key');
            $table->string('pix_key_value', 50);
            $table->unsignedBigInteger('user');
            $table->timestamps();

            // Foreign keys
            $table->foreign('bank')->references('code')->on('banks_bra');
            $table->foreign('pix_key')->references('id')->on('pix_keys');
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');

            Schema::table('banks_bra', function (Blueprint $table) {
                $table->index('code');
            });
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
