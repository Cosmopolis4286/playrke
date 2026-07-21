<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Desactiva los 3 logros con requisito tipo "custom" (lucky_winner, early_bird,
 * night_owl) en bases que ya corrieron AchievementSeeder antes de este cambio.
 *
 * AchievementService::checkCustomRequirement() no tiene implementación (siempre
 * retorna false) — estos logros quedaban en el catálogo como indesbloqueables
 * silenciosos. Se desactivan hasta que se implemente su lógica real.
 */
return new class extends Migration
{
    private const CODES = ['lucky_winner', 'early_bird', 'night_owl'];

    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('achievements')) {
            return;
        }

        DB::table('achievements')
            ->whereIn('code', self::CODES)
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('achievements')
            ->whereIn('code', self::CODES)
            ->update(['is_active' => true]);
    }
};
