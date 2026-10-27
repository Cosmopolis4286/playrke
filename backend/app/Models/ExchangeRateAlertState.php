<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Estado de alertas de una tasa (dueño + moneda) — ver ExchangeRateAlertService.
 */
class ExchangeRateAlertState extends Model
{
    use SnowflakeId;

    protected $casts = [
        'currency_id' => 'string',
        'last_amount' => 'float',
        'stale_notified_at' => 'datetime',
        'deviation_notified_at' => 'datetime',
    ];

    protected $fillable = [
        'owner_key',
        'currency_id',
        'last_amount',
        'stale_notified_at',
        'deviation_notified_at',
    ];

    protected $table = 'exchange_rate_alert_states';
}
