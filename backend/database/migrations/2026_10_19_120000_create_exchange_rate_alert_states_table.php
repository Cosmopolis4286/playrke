<?php

declare(strict_types=1);

use App\Models\AppParameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas de tasas de cambio (ExchangeRateAlertService):
 *  - exchange_rate_alert_states: último valor avisado por dueño de tasa
 *    (plataforma, comercializadora o cliente) y moneda, y cuándo se avisó
 *    tasa desactualizada / desvío — para no repetir avisos.
 *  - Parámetros configurables (AppParameter) con sus valores por defecto.
 */
return new class extends Migration
{
    private const PARAMS = [
        'exchange_alert_change_pct'    => '3',
        'exchange_alert_stale_hours'   => '24',
        'exchange_alert_deviation_pct' => '10',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('exchange_rate_alert_states')) {
            Schema::create('exchange_rate_alert_states', function (Blueprint $table) {
                $table->snowflake('id')->primary();
                $table->string('owner_key', 40)->comment('platform | commerce:{id} | customer:{id}');
                $table->unsignedBigInteger('currency_id');
                $table->decimal('last_amount', 12, 4)->comment('Última tasa avisada a la red');
                $table->timestamp('stale_notified_at')->nullable()->comment('Último aviso de tasa propia desactualizada');
                $table->timestamp('deviation_notified_at')->nullable()->comment('Último aviso de desvío vs. la oficial');
                $table->timestamps();

                $table->unique(['owner_key', 'currency_id'], 'uq_exchange_alert_owner_currency');
            });
        }

        foreach (self::PARAMS as $param => $value) {
            AppParameter::firstOrCreate(['param' => $param], ['value' => $value, 'status' => AppParameter::STATUS_ACTIVE]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_alert_states');
        AppParameter::whereIn('param', array_keys(self::PARAMS))->delete();
    }
};
