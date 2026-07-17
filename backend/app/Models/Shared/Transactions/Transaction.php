<?php

namespace App\Models\Shared\Transactions;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para transacciones del sistema.
 *
 * Representa las transacciones financieras realizadas en el sistema,
 * incluyendo información de cliente, juego, moneda, estado y datos
 * de procesamiento para auditoría y seguimiento.
 */
class Transaction extends Model
{
    use SnowflakeId;

    protected $table = "transactions";

    public const STATUS_WAITING  = 'WAITING';
    public const STATUS_EXECUTED = 'EXECUTED';

    protected $fillable = [
        'customer_id',
        'user_id',
        'game_id',
        'game_mode_id',
        'bet_id',
        'code',
        'transaction_type',
        'currency_id',
        'amount',
        'status',
        'processing_data',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'processing_data' => 'datetime',
    ];
}
