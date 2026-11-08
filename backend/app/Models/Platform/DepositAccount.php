<?php

namespace App\Models\Platform;

use App\Models\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta de la plataforma a la que el jugador transfiere para depositar en
 * su billetera de esa moneda (ver DepositAccountController). El depósito
 * guarda en metadata la cuenta elegida para que el admin sepa dónde verificar.
 */
class DepositAccount extends Model
{
    use SnowflakeId;

    public const METHODS = [
        'pago_movil' => 'Pago móvil',
        'transferencia' => 'Transferencia bancaria',
        'zelle' => 'Zelle',
        'binance' => 'Binance Pay',
        'efectivo' => 'Efectivo',
        'otro' => 'Otro',
    ];

    protected $casts = [
        'id' => 'string',
        'currency_id' => 'string',
        'sort_order' => 'integer',
    ];

    protected $fillable = [
        'currency_id', 'method', 'label', 'bank_name', 'holder_name', 'holder_document',
        'account_identifier', 'instructions', 'status', 'sort_order',
    ];

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function scopeActiveFor(Builder $query, string $currencyId) : Builder
    {
        return $query->where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /** Lo que ve el jugador al depositar. */
    public function toPublicArray() : array
    {
        return [
            'id' => (string) $this->id,
            'method' => $this->method,
            'method_label' => self::METHODS[$this->method] ?? $this->method,
            'label' => $this->label,
            'bank_name' => $this->bank_name,
            'holder_name' => $this->holder_name,
            'holder_document' => $this->holder_document,
            'account_identifier' => $this->account_identifier,
            'instructions' => $this->instructions,
        ];
    }
}
