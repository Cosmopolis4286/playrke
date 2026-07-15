<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea trivia_participants.prize_status al FSM de GamePrizeContract
 * (waiting → processing → claimed | cancelled | suspended), el mismo que
 * usa bingo_card_prizes.prize_status — necesario para que GamePrizeService
 * pague premios de Bingo y Trivia con el mismo código.
 *
 * Reemplaza el esquema anterior (pending/paid/failed) de la migración
 * 2026_07_14_00: no había filas en producción todavía, así que no se
 * requiere backfill de datos.
 *
 * prize_payment_attempts / prize_last_attempted_at se eliminan: el conteo de
 * intentos y el backoff ahora se derivan de trivia_prize_payments (igual que
 * BingoCardPrize deriva de bingo_card_prize_payments, sin columnas propias).
 */
return new class extends Migration
{
    public function up(): void
    {
        $conn = 'pgsql_trivia';

        Schema::connection($conn)->table('trivia_participants', function (Blueprint $table) use ($conn) {
            if (Schema::connection($conn)->hasColumn('trivia_participants', 'prize_payment_attempts')) {
                $table->dropColumn('prize_payment_attempts');
            }
            if (Schema::connection($conn)->hasColumn('trivia_participants', 'prize_last_attempted_at')) {
                $table->dropColumn('prize_last_attempted_at');
            }
        });

        \Illuminate\Support\Facades\DB::connection($conn)->statement(
            "ALTER TABLE trivia_participants ALTER COLUMN prize_status SET DEFAULT 'waiting'"
        );
    }

    public function down(): void
    {
        $conn = 'pgsql_trivia';

        Schema::connection($conn)->table('trivia_participants', function (Blueprint $table) {
            $table->unsignedTinyInteger('prize_payment_attempts')->default(0)->after('prize_status');
            $table->timestamp('prize_last_attempted_at')->nullable()->after('prize_payment_attempts');
        });

        \Illuminate\Support\Facades\DB::connection($conn)->statement(
            "ALTER TABLE trivia_participants ALTER COLUMN prize_status SET DEFAULT 'pending'"
        );
    }
};
