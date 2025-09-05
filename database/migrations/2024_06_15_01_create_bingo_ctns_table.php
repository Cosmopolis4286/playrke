<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_ctns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')->constrained('bingo_rooms')->cascadeOnDelete();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $table->foreignId('room_user_id')->constrained('bingo_rooms_user')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('bingo_ctns_lot')->cascadeOnDelete();

            $table->foreignId('coin_id')->constrained('coins')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID de la moneda asociada');
            $table->string('serial', 20);

            // Columnas del cartón: B, I, N, G, O
            foreach (['b', 'i', 'n', 'g', 'o'] as $letter) {
                for ($i = 1; $i <= 5; $i++) {
                    $nullable = ($letter === 'n' && $i === 3) ? true : false; // N3 puede ser nulo
                    $table->string("{$letter}{$i}", 2)->nullable($nullable);
                }
            }

            $table->string('hash', 64);
            $table->timestamps();

            // Índice compuesto para consultas rápidas por sala
            $table->index(['room']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns');
    }
};
