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
        Schema::create('treasurers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->foreignId('country')->constrained('countries');
            $table->foreignId('coin')->constrained('coins');
            $table->string('phone', 12);
            $table->foreignId('user')->constrained('users');
            $table->uuid('code_uuid')->unique();
            $table->string('status', 1)->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasurers');
    }
};
