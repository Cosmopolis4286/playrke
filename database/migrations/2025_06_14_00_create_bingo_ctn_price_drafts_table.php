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
        Schema::create('bingo_ctn_price_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_room_id')->nullable()->constrained('bingo_rooms');
            $table->foreignId('automatic_bingo_room_id')->nullable()->constrained('automatic_bingo_rooms');
            $table->foreignId('creator_user')->constrained('users');
            $table->json('coins');
            $table->json('ctn_prices');
            $table->enum('status', ['ACTIVE', 'COMPLETED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctn_price_drafts');
    }
};
