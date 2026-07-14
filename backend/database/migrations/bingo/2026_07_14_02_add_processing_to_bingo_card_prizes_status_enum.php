<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega 'processing' al enum prize_status de bingo_card_prizes.
 *
 * BingoPrizePayoutService::payout() marcaba el premio directamente de
 * 'waiting' a 'claimed' una vez acreditada la wallet, sin un estado
 * intermedio que distinguiera "un pago está en curso ahora mismo" de
 * "nadie lo ha intentado todavía" — eso obligaba a inferir la idempotencia
 * comparando contra bingo_card_prize_payments en cada llamada. Con
 * 'processing' el propio prize_status es la fuente de verdad: se setea
 * bajo lockForUpdate() antes de tocar la wallet, así que cualquier llamada
 * concurrente que obtenga el lock después ve processing/claimed y no
 * vuelve a intentar el pago.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bingo_card_prizes MODIFY COLUMN prize_status ENUM('waiting', 'processing', 'claimed', 'cancelled', 'suspended') NOT NULL DEFAULT 'waiting'");
    }

    public function down(): void
    {
        // Reconvertir cualquier fila 'processing' remanente a 'waiting' antes de
        // achicar el enum, para no dejar filas con un valor que ya no existe.
        DB::statement("UPDATE bingo_card_prizes SET prize_status = 'waiting' WHERE prize_status = 'processing'");
        DB::statement("ALTER TABLE bingo_card_prizes MODIFY COLUMN prize_status ENUM('waiting', 'claimed', 'cancelled', 'suspended') NOT NULL DEFAULT 'waiting'");
    }
};
