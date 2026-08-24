<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cierra la referencia blanda que wallet_holds.game_session_id dejó anotada
 * en su propia migración de creación ("sin FK física todavía — se agrega en
 * la migración que cree game_sessions"). nullOnDelete(): un hold no debe
 * desaparecer si la sesión se borra, solo perder la referencia — el hold en
 * sí sigue siendo la fuente de verdad de si el saldo está reservado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_holds', function (Blueprint $table) {
            $table->foreign('game_session_id')
                ->references('id')->on('game_sessions')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_holds', function (Blueprint $table) {
            $table->dropForeign(['game_session_id']);
        });
    }
};
