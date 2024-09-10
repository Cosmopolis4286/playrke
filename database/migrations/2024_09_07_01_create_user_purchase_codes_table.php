<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_purchase_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('lastname', 20);
            $table->unsignedBigInteger('country');
            $table->string('whatsapp_phone', 14);
            $table->timestamps();

            $table->foreign('country')->references('id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_purchase_codes');
    }
};
