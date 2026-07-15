<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * prize_status: 'pending' | 'paid' | 'failed'
 *
 * Antes de esta columna, PrizeDistributionService::distribute() saltaba en
 * silencio (`continue`) a los ganadores sin wallet activa — sin dejar rastro
 * de que un premio quedó sin pagar. prize_status permite detectarlos y
 * reintentar, igual que prize_status en bingo_card_prizes.
 *
 * Solo es significativo para participantes con final_rank no nulo (ganadores);
 * el resto queda en 'pending' por defecto sin que aplique.
 */
return new class extends Migration
{
    public function up(): void
    {
        $conn = 'pgsql_trivia';

        Schema::connection($conn)->table('trivia_participants', function (Blueprint $table) use ($conn) {
            if (!Schema::connection($conn)->hasColumn('trivia_participants', 'prize_status')) {
                $table->string('prize_status', 20)->default('pending')->after('prize_won');
            }
            if (!Schema::connection($conn)->hasColumn('trivia_participants', 'prize_payment_attempts')) {
                $table->unsignedTinyInteger('prize_payment_attempts')->default(0)->after('prize_status');
            }
            if (!Schema::connection($conn)->hasColumn('trivia_participants', 'prize_last_attempted_at')) {
                $table->timestamp('prize_last_attempted_at')->nullable()->after('prize_payment_attempts');
            }
        });

        \Illuminate\Support\Facades\DB::connection($conn)->statement(
            'CREATE INDEX IF NOT EXISTS trivia_participants_prize_status_index ON trivia_participants (prize_status)'
        );
    }

    public function down(): void
    {
        $conn = 'pgsql_trivia';
        $fields = ['prize_status', 'prize_payment_attempts', 'prize_last_attempted_at'];

        Schema::connection($conn)->table('trivia_participants', function (Blueprint $table) use ($conn, $fields) {
            $existing = array_filter($fields, fn($c) => Schema::connection($conn)->hasColumn('trivia_participants', $c));
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};
