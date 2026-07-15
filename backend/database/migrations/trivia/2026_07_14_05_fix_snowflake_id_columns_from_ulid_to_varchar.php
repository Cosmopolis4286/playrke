<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige el tipo de columna de todos los IDs Snowflake en pgsql_trivia.
 *
 * Las migraciones originales declararon estas columnas como ulid() (char(26)
 * en Postgres), pero BaseModel usa el trait SnowflakeId globalmente — genera
 * IDs numéricos de ~19-20 dígitos, no ULIDs. Postgres rellena con espacios
 * hasta 26 caracteres al insertar un valor más corto en una columna char(n).
 *
 * Postgres SÍ compara correctamente estas columnas en cláusulas WHERE (hace
 * padding automático del lado derecho al comparar contra una columna bpchar),
 * así que ningún query de Eloquent se vio afectado. El problema aparece solo
 * en código PHP que lee el valor fuera de una query SQL — ej. para construir
 * una clave de idempotencia o comparar strings directamente — donde el
 * padding con espacios rompe la igualdad esperada.
 *
 * Se cambia a varchar(20): suficiente para el mayor Snowflake posible
 * (19-20 dígitos), sin relleno.
 */
return new class extends Migration
{
    private string $conn = 'pgsql_trivia';

    /** @var array<int, array{table: string, column: string, references: string, onDelete: string}> onDelete tomado de pg_constraint.confdeltype real antes de dropear */
    private array $foreignKeys = [
        ['table' => 'trivia_answers',        'column' => 'game_id',         'references' => 'trivia_games.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_answers',        'column' => 'question_id',     'references' => 'trivia_questions.id',    'onDelete' => 'NO ACTION'],
        ['table' => 'trivia_game_events',    'column' => 'game_id',         'references' => 'trivia_games.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_game_questions', 'column' => 'question_id',     'references' => 'trivia_questions.id',    'onDelete' => 'CASCADE'],
        ['table' => 'trivia_game_questions', 'column' => 'game_id',         'references' => 'trivia_games.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_games',          'column' => 'room_id',         'references' => 'trivia_rooms.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_participants',   'column' => 'room_id',         'references' => 'trivia_rooms.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_participants',   'column' => 'team_id',         'references' => 'trivia_teams.id',        'onDelete' => 'SET NULL'],
        ['table' => 'trivia_prize_payments', 'column' => 'participant_id',  'references' => 'trivia_participants.id', 'onDelete' => 'CASCADE'],
        ['table' => 'trivia_prize_payments', 'column' => 'room_id',         'references' => 'trivia_rooms.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_scores',         'column' => 'game_id',         'references' => 'trivia_games.id',        'onDelete' => 'CASCADE'],
        ['table' => 'trivia_teams',          'column' => 'room_id',         'references' => 'trivia_rooms.id',        'onDelete' => 'CASCADE'],
    ];

    /** @var array<string, array<int, string>> columnas character(26) por tabla, incluye referencias externas sin FK */
    private array $charColumns = [
        'trivia_rooms'          => ['id', 'host_id', 'entry_currency_id', 'local_currency_id', 'prize_currency_id'],
        'trivia_games'          => ['id', 'room_id', 'winner_user_id'],
        'trivia_questions'      => ['id'],
        'trivia_answers'        => ['id', 'game_id', 'question_id'],
        'trivia_scores'         => ['id', 'game_id'],
        'trivia_game_questions' => ['id', 'game_id', 'question_id'],
        'trivia_game_events'    => ['id', 'game_id', 'game_question_id'],
        'trivia_participants'   => ['id', 'room_id', 'user_id', 'paid_currency_id', 'team_id'],
        'trivia_teams'          => ['id', 'room_id'],
        'trivia_skill_ratings'  => ['id'],
        'trivia_prize_payments' => ['id', 'participant_id', 'room_id', 'user_id'],
    ];

    public function up(): void
    {
        $db = DB::connection($this->conn);

        foreach ($this->foreignKeys as $fk) {
            $constraint = "{$fk['table']}_{$fk['column']}_foreign";
            $db->statement("ALTER TABLE {$fk['table']} DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        foreach ($this->charColumns as $table => $columns) {
            foreach ($columns as $column) {
                $db->statement(
                    "ALTER TABLE {$table} ALTER COLUMN {$column} TYPE varchar(20) USING TRIM({$column})::varchar(20)"
                );
            }
        }

        foreach ($this->foreignKeys as $fk) {
            [$refTable, $refColumn] = explode('.', $fk['references']);
            $constraint = "{$fk['table']}_{$fk['column']}_foreign";
            $db->statement(
                "ALTER TABLE {$fk['table']} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$fk['column']}) REFERENCES {$refTable} ({$refColumn}) ON DELETE {$fk['onDelete']}"
            );
        }
    }

    public function down(): void
    {
        $db = DB::connection($this->conn);

        foreach ($this->foreignKeys as $fk) {
            $constraint = "{$fk['table']}_{$fk['column']}_foreign";
            $db->statement("ALTER TABLE {$fk['table']} DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        foreach ($this->charColumns as $table => $columns) {
            foreach ($columns as $column) {
                $db->statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE char(26) USING RPAD({$column}, 26, ' ')");
            }
        }

        foreach ($this->foreignKeys as $fk) {
            [$refTable, $refColumn] = explode('.', $fk['references']);
            $constraint = "{$fk['table']}_{$fk['column']}_foreign";
            $db->statement(
                "ALTER TABLE {$fk['table']} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$fk['column']}) REFERENCES {$refTable} ({$refColumn}) ON DELETE {$fk['onDelete']}"
            );
        }
    }
};
