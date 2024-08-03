<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoinsTable extends Migration
{
    public function up()
    {
        Schema::create('coins', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 6);
            $table->string('short_name', 10);
            $table->string('long_name', 15);
            $table->unsignedBigInteger('country')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Clave foránea
            $table->foreign('country')->references('id')->on('countries')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->dropForeign(['country']);
        });
        Schema::dropIfExists('coins');
    }
}
