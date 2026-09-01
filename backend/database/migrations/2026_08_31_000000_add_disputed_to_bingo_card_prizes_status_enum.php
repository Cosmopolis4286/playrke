<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * bingo_card_prizes ya tiene datos reales (premios 'claimed') cuando se
 * escribió esto — mismo motivo que 2026_08_30_200343_add_triggered_by_to_
 * command_logs_table: no se puede recrear la tabla desde cero, así que el
 * ENUM existente se altera con MODIFY COLUMN. doctrine/dbal no está
 * instalado (Schema::table()->change() no soporta ENUM sin él de todos
 * modos), así que se usa SQL crudo — único enfoque confiable para ampliar
 * un ENUM de MySQL sin recrear la columna.
 *
 * 'disputed': nuevo estado de la FSM de GamePrizeContract (compartida con
 * Trivia, donde prize_status es un string libre y no necesita esta
 * migración) — un premio 'claimed' pasa a 'disputed' cuando un admin
 * revierte el pago manualmente tras detectar fraude posterior al pago
 * (ver GamePrizeService::disputeAndReverse()).
 *
 * El Schema::create original (bingo/2024_01_01_001121_create_bingo_card_
 * prizes_table) también quedó actualizado con 'disputed' en el enum, para
 * que un entorno nuevo (migrate:fresh) lo tenga desde la creación sin
 * depender de esta migración incremental.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bingo_card_prizes')) {
            return;
        }

        DB::statement(
            "ALTER TABLE bingo_card_prizes MODIFY COLUMN prize_status " .
            "ENUM('waiting', 'processing', 'claimed', 'cancelled', 'suspended', 'disputed') " .
            "NOT NULL DEFAULT 'waiting'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('bingo_card_prizes')) {
            return;
        }

        // Revertir el ENUM fallaría si ya existen filas 'disputed' — se deja
        // sin acción destructiva; un rollback real requeriría decidir a mano
        // qué hacer con esas filas primero.
        DB::statement(
            "ALTER TABLE bingo_card_prizes MODIFY COLUMN prize_status " .
            "ENUM('waiting', 'processing', 'claimed', 'cancelled', 'suspended') " .
            "NOT NULL DEFAULT 'waiting'"
        );
    }
};
