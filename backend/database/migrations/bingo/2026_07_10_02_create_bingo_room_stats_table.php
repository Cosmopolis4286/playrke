<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_room_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('room_id')->unique();

            // Métricas de participación
            $table->unsignedInteger('total_lots')->default(0);
            $table->unsignedInteger('total_cards')->default(0);
            $table->unsignedInteger('total_players')->default(0);

            // Métricas financieras (en la moneda base de la sala)
            $table->decimal('total_revenue', 18, 4)->default(0);
            $table->decimal('total_revenue_usd', 18, 4)->default(0);
            $table->decimal('prize_pool_final', 18, 4)->default(0);

            // Métricas de sorteo
            $table->unsignedInteger('total_chips_drawn')->default(0);
            $table->unsignedInteger('total_rounds')->default(0);
            $table->unsignedInteger('winning_rounds')->default(0);
            $table->unsignedSmallInteger('draw_duration_seconds')->nullable();

            // Fecha de cálculo
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_room_stats');
    }
};
