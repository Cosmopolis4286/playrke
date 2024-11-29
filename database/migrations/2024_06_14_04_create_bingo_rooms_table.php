<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bingo_rooms', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('time', 10);
            $table->unsignedBigInteger('status')->default(1);
            $table->integer('unix_date')->nullable();
            $table->timestamp('publication_date')->nullable();
            $table->integer('close_room')->nullable();
            $table->boolean('prizes_paid')->default(0);
            $table->integer('players')->default(0);
            $table->integer('ctns')->default(0);
            $table->double('all_sale')->default(0);
            $table->double('rke_commn')->default(0)->nullable();
            $table->double('creator_commn')->default(0)->nullable();
            $table->double('seller_commn')->default(0);
            $table->double('jackpot')->default(0);
            $table->double('jackpot_prize')->default(0);
            $table->double('serial_prize')->default(0);
            $table->string('encrypted', 18)->unique();
            $table->string('code_uuid', 36)->nullable()->unique();
            $table->timestamps();

            // Definición de las claves foráneas
            $table->foreign('status')->references('id')->on('game_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bingo_rooms', function (Blueprint $table) {
            // Eliminar claves foráneas antes de eliminar la tabla
            $table->dropForeign(['game_mode']);
            $table->dropForeign(['status']);
        });

        Schema::dropIfExists('bingo_rooms');
    }
};
