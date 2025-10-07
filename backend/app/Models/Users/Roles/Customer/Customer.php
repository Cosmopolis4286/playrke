<?php

namespace App\Models\Users\Roles\Customer;

use App\Models\Countries\Country;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'name',
        'description',
        'country_id',
        'user_id',
        'customer_code',
        'phone',
        'commission',
        'all_in',
        'brand',
        'product',
        'logo',
        'link_route',
        'token',
        'customer_key',
        'security_key',
        'status'
    ];

    protected $casts = [
        'all_in' => 'boolean',
        'commission' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
