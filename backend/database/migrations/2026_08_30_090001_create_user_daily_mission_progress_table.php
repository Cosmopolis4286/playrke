<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Progreso de un usuario en una misión diaria, para el día calendario
 * indicado en `date` (UTC — mismo criterio ya aceptado como limitación
 * conocida en LoginStreakService, ver progreso/xp-logros-plan.md Fase 5).
 * Una fila nueva por usuario+misión+día — el "reinicio diario" es
 * simplemente que el día siguiente no hay fila todavía, no un job de reset.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('user_daily_mission_progress');
    }

    public function up() : void
    {
        Schema::create('user_daily_mission_progress', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignSnowflake('daily_mission_id')
                ->constrained('daily_missions')
                ->cascadeOnDelete();

            $table->date('date')->comment('Día calendario (UTC) al que pertenece este progreso');

            $table->unsignedSmallInteger('progress')->default(0);
            $table->timestamp('completed_at')->nullable()->comment('Cuándo se alcanzó el target — null mientras sigue en progreso');

            $table->timestamps();

            $table->unique(['user_id', 'daily_mission_id', 'date'], 'user_daily_mission_unique');
            $table->index(['user_id', 'date']);
        });
    }
};
