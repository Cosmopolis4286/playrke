<?php

namespace App\Models\Payments\ExchangeRate\Users\Roles;

use App\Models\Users\Roles\Customer\Customer;
use App\Models\Currencies\Currency;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerExchangeRate extends Model
{
    use SnowflakeId;

    protected $table = "customers_exchange_rates";

    protected $fillable = [
        'customer_id',
        'currency_id',
        'exchange_rate',
        'percentage',
        'user_id',
        'status'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'percentage' => 'decimal:3'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
