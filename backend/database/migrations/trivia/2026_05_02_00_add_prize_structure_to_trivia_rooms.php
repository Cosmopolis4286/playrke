<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * prize_model: 'guaranteed' | 'pool'
 *
 *   guaranteed  → El organizador define premios fijos antes de abrir la sala.
 *                 El monto NO depende de cuántos jugadores entren.
 *                 Se parece a un torneo/concurso con premio patrocinado.
 *                 Ejemplo: 1° $50, 2° $25, 3° $10 — sin importar si hay 5 o 500 players.
 *
 *   pool        → Los premios se forman con las entradas cobradas.
 *                 Se aplica un rake (comisión de plataforma) transparente.
 *                 El resto se distribuye proporcionalmente entre los top N.
 *                 Ejemplo: 100 players × $1 = $100 pool → rake 10% → $90 a repartir.
 *
 * prize_payouts: JSON array de { rank: int, percent: float (0-1) }
 *   Suma de percent debe ser ≤ 1.0 (el resto cubre el rake en modelo pool,
 *   o simplemente no se reparte en modelo guaranteed).
 *   Distribuir entre al menos 3 posiciones evita la percepción "winner-takes-all".
 *
 * platform_rake_percent: % que retiene la plataforma sobre el pool total (solo modelo pool).
 *   Debe ser visible en el TOS y en la UI antes de que el jugador pague.
 *
 * sponsor_prize: monto extra aportado por un patrocinador externo (suma al pool garantizado).
 *   Hace el modelo "sponsors" (Opción C) completamente compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            // 'guaranteed' | 'pool'
            $table->string('prize_model', 20)->default('guaranteed')->after('commission_percent');

            // [{rank:1, percent:0.60}, {rank:2, percent:0.25}, {rank:3, percent:0.15}]
            $table->jsonb('prize_payouts')->nullable()->after('prize_model');

            // % de comisión de plataforma visible al usuario (solo modelo pool)
            $table->decimal('platform_rake_percent', 5, 2)->default(0)->after('prize_payouts');

            // Monto extra de patrocinador que se suma al bote (ambos modelos)
            $table->decimal('sponsor_prize', 20, 8)->default(0)->after('platform_rake_percent');

            // Pool efectivo acumulado en tiempo real (solo modelo pool)
            $table->decimal('pool_accumulated', 20, 8)->default(0)->after('sponsor_prize');

            $table->index('prize_model');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'prize_model',
                'prize_payouts',
                'platform_rake_percent',
                'sponsor_prize',
                'pool_accumulated',
            ]);
        });
    }
};
