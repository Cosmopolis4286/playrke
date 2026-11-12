<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * TriviaSkillRating extiende BaseModel (SoftDeletes), pero la migración
 * original de trivia_skill_ratings no creó deleted_at. Toda consulta del
 * modelo agrega "where deleted_at is null" y fallaba con "column does not
 * exist": el ELO nunca se actualizaba al terminar una partida, y en
 * PostgreSQL ese error además aborta la transacción en curso.
 */
return new class extends Migration
{
    public function down() : void
    {
        $schema = Schema::connection('pgsql_trivia');

        if ($schema->hasColumn('trivia_skill_ratings', 'deleted_at')) {
            $schema->table('trivia_skill_ratings', function(Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function up() : void
    {
        $schema = Schema::connection('pgsql_trivia');

        if (! $schema->hasColumn('trivia_skill_ratings', 'deleted_at')) {
            $schema->table('trivia_skill_ratings', function(Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
