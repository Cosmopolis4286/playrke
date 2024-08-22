<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer');
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('country')->nullable();
            $table->string('coin', 10);
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();

            $table->foreign('customer')->references('id')->on('customers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('country')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('customer_users', function (Blueprint $table) {
            $table->dropForeign(['country']);
            $table->dropForeign(['customer']);
        });
        Schema::dropIfExists('customer_users');
    }
}
