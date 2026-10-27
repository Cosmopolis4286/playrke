<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Sesión de partida mínima — ver progreso/game-engine-plan.md.
 *
 * Deliberadamente NO es una máquina de estados genérica para Bingo/Trivia
 * (cada uno ya resuelve su propio ciclo de vida con bingo_rooms.status_id /
 * trivia_rooms.status, maduro y en producción). Su único propósito hoy es
 * dar una tabla real detrás de wallet_holds.game_session_id (referencia
 * blanda desde la migración de wallet_holds) para juegos futuros de
 * duración variable (ej. Transportistas vs Cazadores).
 *
 * status usa un enum propio corto (no FK a game_status): ese catálogo está
 * acoplado a la UI de admin de Bingo y tiene estados bingo-céntricos
 * (REFUND, OFFLINE) que no aplican a una sesión genérica.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('game_sessions');
    }

    public function up() : void
    {
        Schema::create('game_sessions', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la sesión de partida');

            $table->string('game', 20)->index()
                ->comment('Slug del juego dueño de esta sesión, ej. "cargas"');

            $table->enum('status', ['WAITING', 'RUNNING', 'FINISHED', 'CANCELLED'])
                ->default('WAITING')
                ->comment('Ciclo de vida de la sesión — enum propio, no FK a game_status');

            $table->foreignSnowflake('initiator_user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que inició la sesión, si aplica');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->json('metadata')->nullable()
                ->comment('Datos específicos del juego que no ameritan columna propia');

            $table->timestamps();

            $table->index(['game', 'status']);
        });
    }
};
