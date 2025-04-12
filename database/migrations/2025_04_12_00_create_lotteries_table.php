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
        Schema::create('lotteries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('code', 7)->nullable();
            $table->string('numbers', 15);
            $table->string('logo', 35)->nullable();
            $table->string('status', 1)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotteries');
    }
};
