<?php

namespace App\Models\Payments\Methods\Brasil;

use App\Enums\Payments\Methods\Brasil\PixKeyType;
use App\Models\Banks\BanksBrasil;
use Illuminate\Database\Eloquent\Model;

class Pix extends Model
{
    protected $table = "pix";

    protected $fillable = [
        'name',
        'bank_id',
        'pix_key',
        'pix_key_value',
        'user_id'
    ];

    protected $appends = ['chave_pix'];

    /**
     * Relación: un Pix pertenece a un banco en Brasil.
     */
    public function bank()
    {
        return $this->belongsTo(BanksBrasil::class, 'bank_id');
    }

    /**
     * Accessor: devuelve el nombre de la clave PIX.
     */
    public function getChavePixAttribute(): string
    {
        $type = PixKeyType::tryFrom($this->pix_key);
        return $type?->label() ?? 'Desconocido';
    }
}
