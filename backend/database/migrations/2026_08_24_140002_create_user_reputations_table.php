<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reputación cacheada del jugador (0-100) — primera versión, ver
 * ReputationService para el cálculo. Score persistido (no calculado on the
 * fly en cada query de matchmaking) porque se deriva de agregados sobre
 * game_participations + anti_cheat_flags, caro de recalcular por request.
 *
 * No es un ledger con historial de cada cambio (se consideró y se descartó
 * para esta primera versión — ver progreso/matchmaking-plan.md): un score
 * simple recalculable desde datos ya existentes es suficiente para que
 * matchmaking filtre, sin la complejidad de auditar cada variación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reputations', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único del registro de reputación');

            $table->foreignSnowflake('user_id')->unique()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Un registro por usuario');

            $table->unsignedTinyInteger('score')->default(100)
                ->comment('0-100. Nuevo usuario arranca en 100 (sin historial negativo todavía)');

            $table->timestamp('calculated_at')->nullable()
                ->comment('Última vez que se recalculó el score');

            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE user_reputations ADD CONSTRAINT chk_user_reputations_score_range CHECK (score >= 0 AND score <= 100)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE user_reputations DROP CHECK chk_user_reputations_score_range');
        }

        Schema::dropIfExists('user_reputations');
    }
};
