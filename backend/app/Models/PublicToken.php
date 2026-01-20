<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PublicToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'scopes',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'scopes'     => 'array',
        'expires_at' => 'datetime',
    ];

    // Scope para tokens válidos (activos y no expirados)
    public function scopeValid($query)
    {
        return $query
            ->where('status', 'ACTIVE')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // Relacion con el usuario propietario del token
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
