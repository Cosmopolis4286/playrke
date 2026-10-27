<?php

declare(strict_types=1);

use App\Models\AppParameter;
use Illuminate\Database\Migrations\Migration;

/*
 * Tope de desvío de las tasas propias vs. la oficial (ExchangeRateDeviationCap).
 * 0 = sin tope (comportamiento previo); el admin lo fija en Parámetros.
 */
return new class extends Migration
{
    public function down() : void
    {
        AppParameter::where('param', 'exchange_rate_max_deviation_pct')->delete();
    }

    public function up() : void
    {
        AppParameter::firstOrCreate(
            ['param' => 'exchange_rate_max_deviation_pct'],
            ['value' => '0', 'status' => AppParameter::STATUS_ACTIVE],
        );
    }
};
