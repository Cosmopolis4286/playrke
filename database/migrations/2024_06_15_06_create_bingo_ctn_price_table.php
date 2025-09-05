<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_ctn_prices', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete();

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID de la moneda asociada');

            // Precio del cartón
            $table->decimal('ctn_price', 12, 2)
                ->comment('Precio del cartón de bingo');

            $table->timestamps();

            // Evita duplicados: mismo room y misma moneda no deben repetirse
            $table->unique(['room_id', 'coin_id'], 'uniq_room_coin_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_ctn_prices');
    }
};
