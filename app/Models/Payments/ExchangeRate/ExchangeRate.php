<?php

namespace App\Models\Payments\ExchangeRate;

use App\Models\Currencies\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'currency_id',
        'value'
    ];

    protected $casts = [
        'value' => 'decimal:4'
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
