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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->unsignedBigInteger('country')->nullable();
            $table->string('phone', 12)->nullable();
            $table->double('commn')->nullable();
            $table->boolean('all_in')->default(0);
            $table->string('brand', 20)->nullable();
            $table->string('product', 30)->nullable();
            $table->string('link_route', 255)->nullable();
            $table->string('token', 255)->nullable();
            $table->string('security_key', 255)->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('country')->references('id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
