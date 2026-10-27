<?php

namespace App\Models\Roles\Customer;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Roles\Customer;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerUser extends Model
{
    use SnowflakeId;

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'brand',
        'token',
        'country_code',
        'currency_id',
        'last_activity',
    ];

    protected $table = 'customers_users';

    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'iso_code');
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
