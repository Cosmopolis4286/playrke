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
        Schema::create('subsidiaries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->unsignedBigInteger('country');
            $table->unsignedBigInteger('coin');
            $table->string('phone', 14);
            $table->double('commn');
            $table->unsignedBigInteger('commerce')->nullable();
            $table->unsignedBigInteger('user');
            $table->string('brand', 20)->nullable();
            $table->string('product', 30)->nullable();
            $table->string('link_route', 255)->nullable();
            $table->string('token', 255);
            $table->string('security_key', 255);
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('country')->references('id')->on('countries');
            $table->foreign('coin')->references('id')->on('coins');
            $table->foreign('commerce')->references('id')->on('commerces');
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidiaries');
    }
};
