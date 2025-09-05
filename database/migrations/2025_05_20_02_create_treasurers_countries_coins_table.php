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
            $table->foreignId('treasurer_id')->constrained('treasurers');
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->foreignId('coin_id')->constrained('coins');
            $table->enum('level', ['1', '2', '3']);
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])->default('ACTIVE');
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
