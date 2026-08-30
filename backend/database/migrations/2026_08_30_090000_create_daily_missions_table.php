<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de misiones diarias — ver progreso/estrategia-producto-plan.md,
 * Fase 6. No existía ningún concepto de "progreso con ventana de tiempo":
 * Achievement/UserAchievement son logros de una sola vez para siempre
 * (whereDoesntHave('users', ...) en AchievementService — no se pueden
 * reutilizar tal cual para algo que se reinicia cada día). Esta tabla es el
 * catálogo (qué misiones existen); el progreso por usuario/día vive en
 * user_daily_mission_progress (migración siguiente).
 *
 * Deliberadamente mínimo: sin fechas de vigencia/temporada, sin
 * segmentación por rol — todas las misiones activas aplican a cualquier
 * jugador, se resetean por día calendario. Si hace falta algo más rico
 * (temporadas, misiones semanales) se extiende cuando haya un caso de uso
 * real, no antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_missions', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la misión');

            $table->string('code', 50)->unique()->comment('Identificador estable, ej. play_3_games');
            $table->string('name', 100)->comment('Nombre visible al jugador');
            $table->string('description', 255)->nullable();

            $table->enum('requirement_type', ['games_played', 'games_won'])
                ->comment('Qué cuenta el progreso — mismo vocabulario que game_participations.won, no el catálogo abierto de Achievement.requirements');
            $table->unsignedSmallInteger('target')->default(1)->comment('Cuántas veces hay que cumplir el requisito en el día');

            $table->unsignedInteger('xp_reward')->default(0);

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
        });

        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE daily_missions ADD CONSTRAINT chk_daily_missions_target_positive CHECK (target > 0)');
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE daily_missions DROP CHECK chk_daily_missions_target_positive');
        }

        Schema::dropIfExists('daily_missions');
    }
};
