<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_prize_ctns', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('room_id')->constrained('bingo_rooms')->cascadeOnDelete();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $table->foreignId('room_user_id')->constrained('bingo_rooms_user')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('bingo_ctns_lot')->cascadeOnDelete();
            $table->foreignId('ctn_id')->constrained('bingo_ctns')->cascadeOnDelete();
            $table->string('coin');

            // Datos del premio
            $table->tinyInteger('round', 2);
            $table->enum('active', ['Y', 'N'])->default('N');
            $table->enum('prize_type', ['FLHE', 'SONG', 'SRL']);
            $table->string('pattern', 50);
            $table->decimal('usd_amount', 12, 2);
            $table->decimal('amount', 12, 8);
            $table->enum('status', ['WT', 'CC', 'CS', 'SP']);

            $table->timestamps();

            // Índices para consultas rápidas
            $table->index(['room_id', 'ctn_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_prize_ctns');
    }
};
