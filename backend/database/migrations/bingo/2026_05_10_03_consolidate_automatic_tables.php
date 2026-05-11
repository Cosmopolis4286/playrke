<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Consolidación de tablas automáticas:
     * - Agregar campos de bingo_rounds_automatic a bingo_rooms_automatic
     * - Agregar campos de bingo_card_price_automatic a bingo_rooms_automatic
     * - Migrar datos existentes
     * - Las tablas antiguas se mantienen para backward compatibility, pero ya no se usan
     */
    public function up(): void
    {
        // Agregar campos de rondas automáticas
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            // Campos de bingo_rounds_automatic consolidados
            $table->json('rounds_config')->nullable()->after('rounds')->comment('Configuración de rondas (tipos, patrones, premios)');
            $table->json('prize_types')->nullable()->after('rounds_config')->comment('Tipos de premios JSON');
            $table->json('bingo_patterns')->nullable()->after('prize_types')->comment('Patrones de bingo JSON');
            $table->json('prize_values')->nullable()->after('bingo_patterns')->comment('Valores de premios JSON');

            // Campos de bingo_card_price_automatic consolidados
            $table->json('card_currencies')->nullable()->after('prize_values')->comment('Monedas para precios automáticos');
            $table->json('card_prices')->nullable()->after('card_currencies')->comment('Precios por moneda para cartas');
        });

        // Migrar datos de bingo_rounds_automatic si existen
        if (Schema::hasTable('bingo_rounds_automatic')) {
            DB::statement(<<<'SQL'
                UPDATE bingo_rooms_automatic bra
                SET 
                    rounds_config = COALESCE((
                        SELECT JSON_OBJECT(
                            'rounds', br.rounds,
                            'prize_types', br.prize_types,
                            'bingo_patterns', br.bingo_patterns,
                            'prize_values', br.prize_values,
                            'status', br.status
                        )
                        FROM bingo_rounds_automatic br
                        WHERE br.automatic_bingo_room_id = bra.id
                        LIMIT 1
                    ), rounds_config),
                    prize_types = COALESCE((
                        SELECT br.prize_types
                        FROM bingo_rounds_automatic br
                        WHERE br.automatic_bingo_room_id = bra.id
                        LIMIT 1
                    ), prize_types),
                    bingo_patterns = COALESCE((
                        SELECT br.bingo_patterns
                        FROM bingo_rounds_automatic br
                        WHERE br.automatic_bingo_room_id = bra.id
                        LIMIT 1
                    ), bingo_patterns),
                    prize_values = COALESCE((
                        SELECT br.prize_values
                        FROM bingo_rounds_automatic br
                        WHERE br.automatic_bingo_room_id = bra.id
                        LIMIT 1
                    ), prize_values)
                WHERE bra.id IN (
                    SELECT DISTINCT automatic_bingo_room_id
                    FROM bingo_rounds_automatic
                    WHERE automatic_bingo_room_id IS NOT NULL
                )
            SQL);
        }

        // Migrar datos de bingo_card_price_automatic si existen
        if (Schema::hasTable('bingo_card_price_automatic')) {
            DB::statement(<<<'SQL'
                UPDATE bingo_rooms_automatic bra
                SET 
                    card_currencies = COALESCE((
                        SELECT bcp.currencies
                        FROM bingo_card_price_automatic bcp
                        WHERE bcp.automatic_bingo_room_id = bra.id
                        LIMIT 1
                    ), card_currencies),
                    card_prices = COALESCE((
                        SELECT bcp.card_prices
                        FROM bingo_card_price_automatic bcp
                        WHERE bcp.automatic_bingo_room_id = bra.id
                        LIMIT 1
                    ), card_prices)
                WHERE bra.id IN (
                    SELECT DISTINCT automatic_bingo_room_id
                    FROM bingo_card_price_automatic
                    WHERE automatic_bingo_room_id IS NOT NULL
                )
            SQL);
        }

        // Crear índices para los nuevos campos
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->index('status', 'idx_bingo_rooms_automatic_status_updated');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn([
                'rounds_config',
                'prize_types',
                'bingo_patterns',
                'prize_values',
                'card_currencies',
                'card_prices',
            ]);
        });
    }
};
