<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reactiva los 3 logros con requisito tipo "custom" (lucky_winner, early_bird,
 * night_owl), desactivados en 2026_07_20_01_deactivate_custom_achievements
 * porque AchievementService::checkCustomRequirement() no tenía implementación.
 *
 * Ya está implementada (ver AchievementService::checkCustomRequirement()):
 *  - lucky_winner: BingoDrawService informa 'lucky_winner_last_call' al ganador.
 *  - early_bird / night_owl: se evalúan contra la hora del servidor (UTC) en el
 *    momento de GameCompletionService::reportParticipation().
 */
return new class extends Migration
{
    private const CODES = ['lucky_winner', 'early_bird', 'night_owl'];

    public function up(): void
    {
        if (! Schema::hasTable('achievements')) {
            return;
        }

        DB::table('achievements')
            ->whereIn('code', self::CODES)
            ->update(['is_active' => true]);
    }

    public function down(): void
    {
        DB::table('achievements')
            ->whereIn('code', self::CODES)
            ->update(['is_active' => false]);
    }
};
