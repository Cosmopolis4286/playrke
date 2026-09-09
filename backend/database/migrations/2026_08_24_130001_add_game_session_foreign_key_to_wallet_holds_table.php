<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
    /**
     * Guard de idempotencia para constraints — Schema::hasColumn() no
     * aplica (la columna ya existe, solo se agrega la FK). Sin esto, un
     * migrate:fresh que corre en paralelo con otra sesión que ya deja la FK
     * físicamente creada pero sin registrar en `migrations` revienta con
     * "Duplicate foreign key constraint name" y aborta toda la corrida de
     * tests antes de llegar al seeder — mismo problema ya visto con
     * receipt_hash.
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }

    public function up(): void
    {
        if ($this->foreignKeyExists('wallet_holds', 'wallet_holds_game_session_id_foreign')) {
            return;
        }

        Schema::table('wallet_holds', function (Blueprint $table) {
            $table->foreign('game_session_id')
                ->references('id')->on('game_sessions')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        if (! $this->foreignKeyExists('wallet_holds', 'wallet_holds_game_session_id_foreign')) {
            return;
        }

        Schema::table('wallet_holds', function (Blueprint $table) {
            $table->dropForeign(['game_session_id']);
        });
    }
};
