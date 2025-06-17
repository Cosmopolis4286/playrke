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
        Schema::create('other_treasurers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user')->unique()->constrained('users')->onDelete('cascade');
            $table->json('allowed_games')->default(json_encode(['ALL']));
            $table->json('allowed_coins');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_treasurers');
    }
};
