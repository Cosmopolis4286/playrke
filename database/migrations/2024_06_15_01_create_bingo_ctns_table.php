<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_ctns', function (Blueprint $table) {
            $table->id()->comment('ID del cartón de bingo');

            // Relaciones
            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Creador del cartón');

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->nullOnDelete()
                ->comment('Vendedor asociado si aplica');

            $table->foreignId('room_user_id')
                ->constrained('bingo_room_users')
                ->cascadeOnDelete()
                ->comment('Usuario en la sala');

            $table->foreignId('lot_id')
                ->constrained('bingo_ctns_lot')
                ->cascadeOnDelete()
                ->comment('Lote del cartón');

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda utilizada');

            $table->string('serial', 20)->comment('Número de serie del cartón');

            // Columnas del cartón: B, I, N, G, O
            foreach (['b', 'i', 'n', 'g', 'o'] as $letter) {
                for ($i = 1; $i <= 5; $i++) {
                    $nullable = ($letter === 'n' && $i === 3);
                    $table->string("{$letter}{$i}", 2)->nullable($nullable);
                }
            }

            $table->string('hash', 64)->comment('Hash único del cartón');

            $table->timestamps();

            // Índices
            $table->index(['room_id', 'creator_user_id'], 'idx_room_creator');
            $table->index(['room_id', 'lot_id'], 'idx_room_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns');
    }
};
