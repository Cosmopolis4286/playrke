<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user');
            $table->unsignedBigInteger('coin'); // Cambio de 'coin' a 'coin'
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('usdt', 10, 2)->default(0.00);
            $table->timestamps();

            // Claves foráneas
            $table->foreign('user')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('coin')->references('id')->on('coins')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Eliminar claves foráneas
            $table->dropForeign(['user']);
            $table->dropForeign(['coin']);
        });
        Schema::dropIfExists('wallets');
    }
}
