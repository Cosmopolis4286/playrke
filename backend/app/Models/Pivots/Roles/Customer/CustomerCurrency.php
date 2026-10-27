<?php

namespace App\Models\Pivots\Roles\Customer;

use App\Models\Currency;
use App\Models\Pivots\BasePivot;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Pivot para las monedas los de clientes.
 *
 * Este pivot:
 * - No posee clave primaria autoincremental
 * - Usa clave primaria compuesta (customer_id, currency_id)
 * - Es auditable mediante identidad serializada
 */
class CustomerCurrency extends BasePivot
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
        'customer_id' => 'string',
        'currency_id' => 'string',
        'level' => 'integer',
    ];

    /**
     * Atributos asignables.
     */
    protected $fillable = [
        'customer_id',
        'currency_id',
        'level',
        'status',
    ];

    /**
     * Indica que la clave primaria es compuesta.
     */
    protected $keyType = 'string';

    /**
     * Define las claves que forman la identidad compuesta para auditoría.
     *
     * @var array<int, string>
     */
    protected array $pivotIdentityKeys = ['customer_id', 'currency_id'];

    /**
     * El pivot usa clave primaria compuesta.
     */
    protected $primaryKey = ['customer_id', 'currency_id'];

    /**
     * Tabla asociada al pivot.
     */
    protected $table = 'customers_currencies';

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
    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
