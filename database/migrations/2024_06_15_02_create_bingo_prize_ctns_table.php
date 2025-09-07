<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_prize_ctns', function (Blueprint $table) {
            $table->id()->comment('ID del premio del cartón');

            // Relaciones
            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala del bingo');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador del premio');

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
                ->comment('Lote asociado');

            $table->foreignId('ctn_id')
                ->constrained('bingo_ctns')
                ->cascadeOnDelete()
                ->comment('Cartón ganador');

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda asociada');

            // Datos del premio
            $table->string('round', 2)->comment('Ronda del juego');
            $table->enum('active', ['Y', 'N'])->default('N')->comment('Y = activo, N = inactivo');
            $table->enum('prize_type', ['FLHE', 'SONG', 'SRL'])->comment('Tipo de premio: FLHE, SONG, SRL');
            $table->string('pattern', 50)->comment('Patrón del cartón ganador');
            $table->decimal('usd_amount', 12, 2)->comment('Monto en USD');
            $table->decimal('amount', 12, 8)->comment('Monto en moneda local');
            $table->enum('status', ['WT', 'CC', 'CS', 'SP'])->comment('Estado del premio');

            $table->timestamps();

            // Índices compuestos para búsquedas frecuentes
            $table->index(['room_id', 'ctn_id'], 'idx_room_ctn');
            $table->index(['room_id', 'creator_user_id'], 'idx_room_creator');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_prize_ctns');
    }
};
