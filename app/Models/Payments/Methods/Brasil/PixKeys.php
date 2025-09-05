<?php

namespace App\Models\Payments\Methods\Brasil;

use App\Models\Banks\BanksBrasil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que representa las claves Pix asociadas a un usuario en Brasil.
 */
class PixKeys extends Model
{
    protected $table = 'pix_keys';

    protected $fillable = [
        'name',
        'bank_id',
        'pix_key_type',
        'pix_key_value',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'bank_id' => 'integer',
    ];

    protected $appends = ['chave_pix'];

    /**
     * Relación: un Pix pertenece a un banco en Brasil.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(BanksBrasil::class, 'bank_id');
    }

    /**
     * Accesor: retorna la clave Pix completa.
     */
    public function getChavePixAttribute(): string
    {
        return $this->pix_key_value;
    }
}
