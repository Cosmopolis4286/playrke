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
        Schema::create('pago_movil', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_type');
            $table->string('document', 10);
            $table->unsignedBigInteger('bank');
            $table->string('phone', 11);
            $table->unsignedBigInteger('user');
            $table->timestamps();

            // Foreign keys
            $table->foreign('document_type')->references('id')->on('document_type');
            $table->foreign('bank')->references('id')->on('banks_ven');
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pago_movil');
    }
};
