<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_card_price_automatic', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del precio automático');

            $table->foreignSnowflake('bingo_room_id')
                ->nullable()
                ->constrained('bingo_rooms')
                ->nullOnDelete()
                ->comment('Sala de bingo asociada');

            $table->foreignSnowflake('automatic_bingo_room_id')
                ->nullable()
                ->constrained('bingo_rooms_automatic')
                ->nullOnDelete()
                ->comment('Sala automática asociada');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador');

            $table->json('currencies')->comment('Monedas permitidas. Ej: ["USD","BRL"]');
            $table->json('card_prices')->comment('Precios por moneda. Ej: {"USD": 5, "BRL": 25}');

            $table->enum('status', ['ACTIVE', 'EXECUTED'])
                ->default('ACTIVE')
                ->comment('Estado del precio automático');

            $table->timestamps();
            $table->index('status', 'idx_bingo_card_price_auto_status');
            $table->index('created_at', 'idx_bingo_card_price_auto_created');

            // Opcional: evitar duplicados por combinación
            $table->unique(
                ['bingo_room_id', 'automatic_bingo_room_id', 'creator_user_id'],
                'unique_draft_per_room_creator'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_card_price_automatic');
    }
};
