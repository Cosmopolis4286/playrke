<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
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
 *
 * game_mode_id: referencia "blanda" al catálogo dinámico `game_modes` (MySQL,
 * tabla `games`+`game_modes`). trivia_rooms vive en Postgres (conexión
 * pgsql_trivia) y game_modes vive en MySQL — son motores distintos, por lo
 * que game_mode_id no lleva FK física, se valida en la capa de aplicación
 * (FormRequest + GameModeRepository).
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_rooms');
    }

    public function up() : void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_rooms')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_rooms', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('code', 10)->unique();
            $table->string('host_id', 20); // External reference (Laravel), indexado abajo
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('waiting'); // Flexible: waiting, playing, finished, cancelled
            $table->unsignedInteger('max_players')->default(10);
            $table->unsignedInteger('question_count')->default(10);
            $table->unsignedInteger('time_per_question')->default(15);
            $table->string('local_currency_id', 20)->nullable();
            $table->string('prize_type', 20)->default('accumulated');
            $table->decimal('prize_amount', 20, 8)->default(0);
            $table->string('prize_currency_id', 20)->nullable();
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('prize_model', 20)->default('guaranteed');
            $table->jsonb('prize_payouts')->nullable();
            $table->decimal('platform_rake_percent', 5, 2)->default(0);
            $table->decimal('sponsor_prize', 20, 8)->default(0);
            $table->decimal('pool_accumulated', 20, 8)->default(0);
            $table->jsonb('fee_by_currency')->nullable();
            $table->jsonb('settings')->nullable(); // Configuración personalizada (jsonb para PostgreSQL)
            $table->jsonb('metadata')->nullable(); // Datos adicionales (jsonb para PostgreSQL)
            $table->unsignedInteger('current_players')->default(0); // Evita contar en runtime
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('category', 50)->nullable();
            $table->unsignedBigInteger('game_mode_id')->nullable();
            $table->jsonb('teams_config')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('host_id');
            $table->index(['status', 'scheduled_at']);
            $table->index('prize_type');
            $table->index('category');
            $table->index('prize_model');
            $table->index('game_mode_id');
        });
    }
};
