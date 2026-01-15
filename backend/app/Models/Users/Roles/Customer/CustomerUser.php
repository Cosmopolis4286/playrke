<?php

namespace App\Models\Users\Customer;

use App\Models\Users\Customer\Customer;
use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerUser extends Model
{
    use SnowflakeId;

    protected $table = "customers_users";

    protected $fillable = [
        'customer_id',
        'user_id',
        'brand',
        'token',
        'country_code',
        'currency_id',
        'last_activity'
    ];

    protected $casts = [
        'last_activity' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'iso_code');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
