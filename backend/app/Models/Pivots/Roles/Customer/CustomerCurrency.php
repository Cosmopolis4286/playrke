<?php

namespace App\Models\Pivots\Roles\Customer;

use App\Models\Pivots\BasePivot;
use App\Models\Currency;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Pivot para las monedas los de clientes.
 *
 * Este pivot:
 * - No posee clave primaria propia
 * - Se identifica por una clave compuesta
 * - Es auditable mediante identidad serializada
 */
class CustomerCurrency extends BasePivot
{
    /**
     * El pivot no es autoincremental.
     */
    public $incrementing = false;

    /**
     * El pivot no tiene primary key tradicional.
     *
     * Eloquent espera un string, no null.
     */
    protected $primaryKey = '';

    /**
     * Tabla asociada al pivot.
     */
    protected $table = 'customers_currencies';

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
     * Casts de atributos.
     */
    protected $casts = [
        'customer_id' => 'string',
        'currency_id' => 'string',
        'level' => 'integer'
    ];

    /**
     * Indica si el modelo usa timestamps.
     *
     * Ajusta a false si la tabla no los tiene.
     */
    public $timestamps = true;

    /**
     * Define las claves que forman la identidad compuesta para auditoría.
     *
     * @var array<int, string>
     */
    protected array $pivotIdentityKeys = ['customer_id', 'currency_id'];

    /**
     * Relación con el comercio.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relación con la moneda.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
