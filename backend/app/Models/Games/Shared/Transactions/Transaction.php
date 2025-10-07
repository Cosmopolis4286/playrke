<?php

namespace App\Models\Games\Shared\Transactions;

use App\Traits\HasSnowflakeId;
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
    use HasSnowflakeId;

    protected $table = "transactions";

    protected $fillable = [
        'customer',
        'user_id',
        'game',
        'game_mode',
        'bet_id',
        'code',
        'transaction_type',
        'currency',
        'amount',
        'status',
        'processing_data'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_data' => 'array'
    ];
}
