<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_creators', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('room_id')->constrained('bingo_rooms')->cascadeOnDelete();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();

            // Datos del creador
            $table->decimal('creator_commn', 15, 2)->default(0); // Comisión del creador
            $table->decimal('ctn_price', 15, 2);                 // Precio del cartón

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            // Evitar duplicados de creador por sala
            $table->unique(['room_id', 'creator_user_id'], 'unique_creator_per_room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_creators');
    }
};
