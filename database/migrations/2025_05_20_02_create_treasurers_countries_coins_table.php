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
        Schema::create('treasurers_countries_coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasurer')->constrained('treasurers');
            $table->foreignId('country')->constrained('countries')->nullable();
            $table->foreignId('coin')->constrained('coins');
            $table->foreignId('creator_user')->constrained('users')->onDelete('cascade');
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasurers_countries_coins');
    }
};
