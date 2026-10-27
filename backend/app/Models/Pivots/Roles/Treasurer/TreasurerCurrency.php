<?php

namespace App\Models\Pivots\Roles\Treasurer;

use App\Models\Currency;
use App\Models\Pivots\BasePivot;
use App\Models\Roles\Treasurer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Pivot para las monedas de los tesoreros.
 *
 * Este pivot:
 * - No posee clave primaria propia
 * - Se identifica por una clave compuesta
 * - Es auditable mediante identidad serializada
 */
class TreasurerCurrency extends BasePivot
{
    /**
     * El pivot no es autoincremental.
     */
    public $incrementing = false;

    /**
     * Indica si el modelo usa timestamps.
     *
     * Ajusta a false si la tabla no los tiene.
     */
    public $timestamps = true;

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'treasurer_id' => 'string',
        'currency_id' => 'string',
        'level' => 'integer',
    ];

    /**
     * Atributos asignables.
     */
    protected $fillable = [
        'treasurer_id',
        'currency_id',
        'level',
        'status',
    ];

    /**
     * Define las claves que forman la identidad compuesta para auditoría.
     *
     * @var array<int, string>
     */
    protected array $pivotIdentityKeys = ['treasurer_id', 'currency_id'];

    /**
     * El pivot no tiene primary key tradicional.
     *
     * Eloquent espera un string, no null.
     */
    protected $primaryKey = '';

    /**
     * Tabla asociada al pivot.
     */
    protected $table = 'treasurers_currencies';

    /**
     * Relación con la moneda.
     */
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con el comercio.
     */
    public function treasurer() : BelongsTo
    {
        return $this->belongsTo(Treasurer::class, 'treasurer_id');
    }
}
