<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicToken extends Model
{
    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
    ];
    protected $fillable = [
        'user_id',
        'token_hash',
        'scopes',
        'expires_at',
        'status',
    ];

    // Scope para tokens válidos (activos y no expirados)
    public function scopeValid($query)
    {
        return $query
            ->where('status', 'ACTIVE')
            ->where(function($q) {
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
