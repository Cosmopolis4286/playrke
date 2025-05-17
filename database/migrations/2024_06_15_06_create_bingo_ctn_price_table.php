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
        Schema::create('bingo_ctn_price', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room')->constrained('bingo_rooms')->onDelete('cascade');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->string('coin', 15);
            $table->double('ctn_price');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctn_price');
    }
};
