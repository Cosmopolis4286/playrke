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
            $table->string('lastname', 20)->nullable();
            $table->unsignedBigInteger('country');
            $table->string('phone', 14);
            $table->string('code', 8);
            $table->string('status', 12);
            $table->timestamp('expiration_date')->nullable();
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
