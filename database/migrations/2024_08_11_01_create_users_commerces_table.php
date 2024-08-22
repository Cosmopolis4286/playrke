<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersCommercesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_commerces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commerce');
            $table->unsignedBigInteger('subsidiary')->nullable();
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('country')->nullable();
            $table->string('coin', 10);
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();

            $table->foreign('commerce')->references('id')->on('commerces')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('country')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('subsidiary')->references('id')->on('subsidiaries')->onDelete('cascade')->onUpdate('cascade');
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
