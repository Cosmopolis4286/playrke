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
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->decimal('entry_fee', 20, 8)->nullable()->after('local_currency_id')
                ->comment('Costo de entrada en entry_currency_id (legado mono-moneda, previo a fee_by_currency)');
            $table->decimal('entry_fee_usd', 20, 8)->nullable()->after('entry_fee')
                ->comment('Costo de entrada en USD, usado como base para conversión cuando la sala no tiene moneda específica configurada');
            $table->string('entry_currency_id', 20)->nullable()->after('entry_fee_usd')
                ->comment('Moneda de entry_fee — referencia blanda a currencies.id (MySQL), sin FK física (cross-database, igual que prize_currency_id)');

            $table->index('entry_currency_id');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropIndex(['entry_currency_id']);
            $table->dropColumn(['entry_fee', 'entry_fee_usd', 'entry_currency_id']);
        });
    }
};
