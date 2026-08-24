<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TriviaRoom::$fillable (app/Domains/Games/Trivia/Models/TriviaRoom.php) y el
 * cobro de entrada "legado" mono-moneda (JoinRoomAction::chargeEntryFee(),
 * TriviaGameController — la rama sin fee_by_currency configurado) usan
 * entry_fee/entry_fee_usd/entry_currency_id desde siempre, pero la migración
 * original de trivia_rooms nunca las creó — solo tiene fee_by_currency
 * (jsonb, el diseño multi-moneda). Cualquier sala sin fee_by_currency
 * configurado rompía con "column entry_fee_usd does not exist" al intentar
 * cobrar la entrada (SELECT * de TriviaRoom::find() trae la columna
 * inexistente). Ver incidente en system_logs, primera aparición 2026-08-18.
 *
 * entry_currency_type SÍ está en $fillable pero no se usa en ningún flujo
 * real (solo esa declaración) — no se agrega aquí, sin evidencia de que
 * haga falta.
 */
return new class extends Migration
{
    /**
     * Cada columna/índice se guarda individualmente contra hasColumn()/hasIndex()
     * en vez de un solo bloque Schema::table(): esta migración ya se aplicó
     * parcialmente al menos una vez en dev (crash del entrypoint a mitad del
     * ciclo migrate, ver feedback_migrations_reconciliation_pattern en memoria
     * del proyecto) y volvió a correr con las columnas ya creadas, fallando con
     * SQLSTATE[42701] "column already exists". Guardar por columna hace que un
     * reintento parcial complete solo lo que falta en vez de fallar entero.
     */
    public function up(): void
    {
        $schema = Schema::connection('pgsql_trivia');

        if (!$schema->hasColumn('trivia_rooms', 'entry_fee')) {
            $schema->table('trivia_rooms', function (Blueprint $table) {
                $table->decimal('entry_fee', 20, 8)->nullable()->after('local_currency_id')
                    ->comment('Costo de entrada en entry_currency_id (legado mono-moneda, previo a fee_by_currency)');
            });
        }

        if (!$schema->hasColumn('trivia_rooms', 'entry_fee_usd')) {
            $schema->table('trivia_rooms', function (Blueprint $table) {
                $table->decimal('entry_fee_usd', 20, 8)->nullable()->after('entry_fee')
                    ->comment('Costo de entrada en USD, usado como base para conversión cuando la sala no tiene moneda específica configurada');
            });
        }

        if (!$schema->hasColumn('trivia_rooms', 'entry_currency_id')) {
            $schema->table('trivia_rooms', function (Blueprint $table) {
                $table->string('entry_currency_id', 20)->nullable()->after('entry_fee_usd')
                    ->comment('Moneda de entry_fee — referencia blanda a currencies.id (MySQL), sin FK física (cross-database, igual que prize_currency_id)');
            });
        }

        if (!$schema->hasIndex('trivia_rooms', 'trivia_rooms_entry_currency_id_index')) {
            $schema->table('trivia_rooms', function (Blueprint $table) {
                $table->index('entry_currency_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('pgsql_trivia');

        if ($schema->hasIndex('trivia_rooms', 'trivia_rooms_entry_currency_id_index')) {
            $schema->table('trivia_rooms', function (Blueprint $table) {
                $table->dropIndex(['entry_currency_id']);
            });
        }

        $columns = array_filter(
            ['entry_fee', 'entry_fee_usd', 'entry_currency_id'],
            fn (string $column) => $schema->hasColumn('trivia_rooms', $column)
        );

        if ($columns !== []) {
            $schema->table('trivia_rooms', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
