<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas necesarias para que MatchmakingService pueda filtrar sesiones
 * por rango de entrada/moneda/cupo con queries SQL normales, en vez de
 * depender de game_sessions.metadata (json, no indexable eficientemente).
 *
 * No se agregaron en la migración original de game_sessions (Fase 1 de
 * progreso/game-engine-plan.md) porque no había consumidor real todavía —
 * ahora sí lo hay: MatchmakingService (Fase 6).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard de idempotencia — mismo patrón que
        // 2026_08_31_000100_add_pending_debt_to_wallets_table: sin esto, un
        // migrate:fresh que corre en paralelo con otra sesión que ya deja
        // estas columnas físicamente creadas pero sin registrar en
        // `migrations` revienta con "Column already exists" y aborta toda
        // la corrida de tests antes de llegar al seeder.
        if (Schema::hasColumn('game_sessions', 'entry_fee')) {
            return;
        }

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->decimal('entry_fee', 15, 2)->nullable()->after('status')
                ->comment('Costo de entrada para unirse a esta sesión, en currency_id. Null = sin costo/no aplica.');

            $table->foreignSnowflake('currency_id')->nullable()
                ->after('entry_fee')
                ->constrained('currencies')
                ->nullOnDelete()
                ->comment('Moneda de entry_fee/prize_pool');

            $table->decimal('prize_pool', 15, 2)->nullable()->after('currency_id')
                ->comment('Bote acumulado de esta sesión, si aplica');

            $table->unsignedSmallInteger('max_participants')->nullable()->after('prize_pool')
                ->comment('Cupo máximo de participantes. Null = sin límite.');

            $table->unsignedSmallInteger('min_level')->nullable()->after('max_participants')
                ->comment('Nivel mínimo (UserLevel.current_level) requerido para unirse. Null = sin restricción.');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE game_sessions ADD CONSTRAINT chk_game_sessions_entry_fee_nonneg CHECK (entry_fee IS NULL OR entry_fee >= 0)');
            DB::statement('ALTER TABLE game_sessions ADD CONSTRAINT chk_game_sessions_prize_pool_nonneg CHECK (prize_pool IS NULL OR prize_pool >= 0)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('game_sessions', 'entry_fee')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE game_sessions DROP CHECK chk_game_sessions_entry_fee_nonneg');
            DB::statement('ALTER TABLE game_sessions DROP CHECK chk_game_sessions_prize_pool_nonneg');
        }

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['entry_fee', 'currency_id', 'prize_pool', 'max_participants', 'min_level']);
        });
    }
};
