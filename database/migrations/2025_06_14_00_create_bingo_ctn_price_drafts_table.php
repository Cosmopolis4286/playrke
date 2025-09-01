<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_ctn_price_drafts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bingo_room_id')
                ->nullable()
                ->constrained('bingo_rooms')
                ->nullOnDelete();

            $table->foreignId('automatic_bingo_room_id')
                ->nullable()
                ->constrained('automatic_bingo_rooms')
                ->nullOnDelete();

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->json('coins');       // Ej: ["USD","BRL"]
            $table->json('ctn_prices');  // Ej: {"USD": 5, "BRL": 25}

            $table->enum('status', ['active', 'finished'])->default('active');

            $table->timestamps();

            // Opcional: evitar duplicados por combinación
            $table->unique(
                ['bingo_room_id', 'automatic_bingo_room_id', 'creator_user_id'],
                'unique_draft_per_room_creator'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_ctn_price_drafts');
    }
};
