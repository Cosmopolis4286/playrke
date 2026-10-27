<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Participación EN CURSO en una game_session — distinta de
 * game_participations (que registra el RESULTADO FINAL de una partida, ya
 * poblada por GameCompletionService para Bingo/Trivia).
 *
 * Consumidor real: MatchmakingService necesita saber cuántos jugadores
 * ocupan una sesión AHORA para no ofrecerla si está llena, algo que
 * game_participations no puede responder (solo existe una fila ahí cuando
 * la partida ya terminó).
 *
 * status distingue joined (activo en la sesión) de left (salió antes de que
 * termine, ej. abandono) — game_participations no modela esto porque no
 * necesita saberlo (solo le importa si al final ganó o no).
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('game_session_participants');
    }

    public function up() : void
    {
        Schema::create('game_session_participants', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la participación en curso');

            $table->foreignSnowflake('game_session_id')
                ->constrained('game_sessions')
                ->cascadeOnDelete()
                ->comment('Sesión a la que pertenece esta participación');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Jugador');

            $table->enum('status', ['JOINED', 'LEFT'])
                ->default('JOINED')
                ->comment('JOINED: ocupa cupo activamente. LEFT: salió antes del cierre de la sesión.');

            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->unique(['game_session_id', 'user_id'], 'game_session_participants_session_user_unique');
            $table->index(['game_session_id', 'status']);
        });
    }
};
