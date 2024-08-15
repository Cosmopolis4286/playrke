<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_providers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user');
            $table->unsignedBigInteger('country')->nullable();
            $table->unsignedBigInteger('provider');
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();

            $table->foreign('country')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('provider')->references('id')->on('providers')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('users_providers', function (Blueprint $table) {
            $table->dropForeign(['country']);
            $table->dropForeign(['provider']);
        });
        Schema::dropIfExists('users_providers');
    }
}
