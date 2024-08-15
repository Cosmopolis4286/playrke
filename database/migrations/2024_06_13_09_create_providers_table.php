<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 25)->unique();
            $table->unsignedBigInteger('user');
            $table->string('pvr_type', 2);
            $table->string('brand', 20)->nullable();
            $table->string('product', 30)->nullable();
            $table->string('link_route', 255)->nullable();
            $table->string('token', 255)->nullable();
            $table->string('security_key', 255);
            $table->boolean('status')->default(1);
            $table->timestamps();

            // Definición de la clave foránea para 'user'
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
        Schema::dropIfExists('providers');
    }
}
