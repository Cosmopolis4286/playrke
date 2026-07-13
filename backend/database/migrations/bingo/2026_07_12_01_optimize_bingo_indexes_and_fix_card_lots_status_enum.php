<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // -----------------------------------------------------------------
        // 1) bingo_card_lots: el enum de 'status' no incluye 'REFUNDED',
        //    pero BingoCardLotRefundJob y RetryFailedBingoRefundsCommand
        //    hacen ->update(['status' => 'REFUNDED']). Con strict mode
        //    en MySQL esto falla en runtime. Se agrega el valor faltante.
        // -----------------------------------------------------------------
        DB::statement("ALTER TABLE bingo_card_lots MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE', 'REFUNDED') NOT NULL DEFAULT 'ACTIVE'");

        Schema::table('bingo_card_lots', function (Blueprint $table) {
            // room_user_id se filtra solo (sin room_id) en BingoCardLotRepository
            $table->index('room_user_id', 'idx_bingo_card_lots_room_user_id');
            // whereBetween('created_at', ...) en reportes por rango de fecha
            $table->index('created_at', 'idx_bingo_card_lots_created_at');
        });

        // -----------------------------------------------------------------
        // 2) bingo_rooms_config: cron de expiración filtra por unix_date;
        //    el lobby filtra por from_house junto con la ventana de fecha.
        // -----------------------------------------------------------------
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->index('unix_date', 'idx_bingo_rooms_config_unix_date');
            $table->index(['from_house', 'date'], 'idx_bingo_rooms_config_house_date');
        });

        // -----------------------------------------------------------------
        // 3) bingo_room_users: falta cubrir (room_id, winner) para
        //    obtención de ganadores/no ganadores por sala, y (user_id,
        //    created_at) para el historial de partidas del jugador.
        // -----------------------------------------------------------------
        Schema::table('bingo_room_users', function (Blueprint $table) {
            $table->index(['room_id', 'winner'], 'idx_bingo_room_users_room_winner');
            $table->index(['user_id', 'created_at'], 'idx_bingo_room_users_user_created');
        });

        // -----------------------------------------------------------------
        // 4) bingo_card_payments: falta índice de apoyo para el patrón
        //    de limpieza/expiración de pagos pendientes por fecha.
        // -----------------------------------------------------------------
        Schema::table('bingo_card_payments', function (Blueprint $table) {
            $table->index(['payment_status', 'created_at'], 'idx_bingo_card_payments_status_created');
        });

        // -----------------------------------------------------------------
        // 5) bingo_rounds: filtro repetido (room_id + whereIn status) en
        //    BingoRoundRepository/BingoWinnerDetectionService.
        // -----------------------------------------------------------------
        Schema::table('bingo_rounds', function (Blueprint $table) {
            $table->index(['room_id', 'status'], 'idx_bingo_rounds_room_status');
        });

        // -----------------------------------------------------------------
        // 6) bingo_cards: reportes por sala de cartones personalizados
        //    (is_custom_card) y orden por validated_at en ganadores.
        // -----------------------------------------------------------------
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->index(['room_id', 'is_custom_card'], 'idx_bingo_cards_room_custom');
            $table->index('validated_at', 'idx_bingo_cards_validated_at');
        });

        // -----------------------------------------------------------------
        // 7) bingo_card_prizes: limpieza de premios cancelados por fecha.
        // -----------------------------------------------------------------
        Schema::table('bingo_card_prizes', function (Blueprint $table) {
            $table->index(['prize_status', 'updated_at'], 'idx_bingo_card_prizes_status_updated');
        });

        // -----------------------------------------------------------------
        // 8) bingo_card_prices: filtro por creador sin índice de apoyo.
        // -----------------------------------------------------------------
        Schema::table('bingo_card_prices', function (Blueprint $table) {
            $table->index('creator_user_id', 'idx_bingo_card_prices_creator');
        });

        // -----------------------------------------------------------------
        // 9) bingo_draws: reconstrucción de la secuencia de sorteo
        //    (where room_id orderBy time) es la consulta más caliente
        //    del juego en vivo; y limpieza histórica por created_at.
        // -----------------------------------------------------------------
        Schema::table('bingo_draws', function (Blueprint $table) {
            $table->index(['room_id', 'time'], 'idx_bingo_draws_room_time');
            $table->index('created_at', 'idx_bingo_draws_created_at');
        });

        // -----------------------------------------------------------------
        // 10) bingo_card_prize_payments: dashboard de creador (filtro +
        //     orden por fecha) y reintentos de pagos fallidos recientes.
        // -----------------------------------------------------------------
        Schema::table('bingo_card_prize_payments', function (Blueprint $table) {
            $table->index(['creator_user_id', 'created_at'], 'idx_bingo_cpp_creator_created');
            $table->index(['payment_status', 'created_at'], 'idx_bingo_cpp_status_created');
        });

        // -----------------------------------------------------------------
        // 11) bingo_rooms_winners: falta (round_id, award_status) y
        //     room_user_id (usados en BingoRoomWinnerRepository).
        // -----------------------------------------------------------------
        Schema::table('bingo_rooms_winners', function (Blueprint $table) {
            $table->index(['round_id', 'award_status'], 'idx_bingo_rooms_winners_round_status');
            $table->index('room_user_id', 'idx_bingo_rooms_winners_room_user');
        });

        // -----------------------------------------------------------------
        // 12) bingo_room_stats: room_id no tenía FK real (solo unique
        //     "pelado"), rompiendo la integridad referencial del resto
        //     del módulo. Se agrega la constraint con cascadeOnDelete
        //     (son estadísticas derivadas, no tiene sentido conservarlas
        //     huérfanas si se borra la sala).
        // -----------------------------------------------------------------
        Schema::table('bingo_room_stats', function (Blueprint $table) {
            $table->foreign('room_id', 'fk_bingo_room_stats_room_id')
                ->references('id')->on('bingo_rooms')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bingo_room_stats', function (Blueprint $table) {
            $table->dropForeign('fk_bingo_room_stats_room_id');
        });

        Schema::table('bingo_rooms_winners', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_rooms_winners_round_status');
            $table->dropIndex('idx_bingo_rooms_winners_room_user');
        });

        Schema::table('bingo_card_prize_payments', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_cpp_creator_created');
            $table->dropIndex('idx_bingo_cpp_status_created');
        });

        Schema::table('bingo_draws', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_draws_room_time');
            $table->dropIndex('idx_bingo_draws_created_at');
        });

        Schema::table('bingo_card_prices', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_card_prices_creator');
        });

        Schema::table('bingo_card_prizes', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_card_prizes_status_updated');
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_cards_room_custom');
            $table->dropIndex('idx_bingo_cards_validated_at');
        });

        Schema::table('bingo_rounds', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_rounds_room_status');
        });

        Schema::table('bingo_card_payments', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_card_payments_status_created');
        });

        Schema::table('bingo_room_users', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_room_users_room_winner');
            $table->dropIndex('idx_bingo_room_users_user_created');
        });

        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_rooms_config_unix_date');
            $table->dropIndex('idx_bingo_rooms_config_house_date');
        });

        Schema::table('bingo_card_lots', function (Blueprint $table) {
            $table->dropIndex('idx_bingo_card_lots_room_user_id');
            $table->dropIndex('idx_bingo_card_lots_created_at');
        });

        DB::statement("ALTER TABLE bingo_card_lots MODIFY COLUMN status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE'");
    }
};
