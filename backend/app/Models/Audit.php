<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audits';

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'auditable_id' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
