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
        Schema::create('bingo_types_plays', function (Blueprint $table) {
            $table->id();
            $table->enum('type_play', ['UNIQUE', 'GROUP'])->default('UNIQUE');
            $table->string('name', 20);
            $table->foreignId('user')->constrained('users')->onDelete('cascade')->nullable();
            $table->string('boxes', 50);
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_types_plays');
    }
};
