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
        Schema::create('game_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('description', 20);
            $table->string('code', 4);
            $table->unsignedBigInteger('game');
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Clave única compuesta
            $table->unique(['game', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_prizes');
    }
};
