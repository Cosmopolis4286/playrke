<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer');
            $table->string('user_id');
            $table->string('token', 255);
            $table->string('country', 3)->nullable();
            $table->string('coin', 15);
            $table->timestamp('last_activity');
            $table->timestamps();

            $table->foreign('customer')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('customers_users', function (Blueprint $table) {
            $table->dropForeign(['country']);
            $table->dropForeign(['customer']);
        });
        Schema::dropIfExists('customers_users');
    }
}
