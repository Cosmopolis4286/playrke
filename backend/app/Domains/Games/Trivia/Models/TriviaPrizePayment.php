<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de intentos de pago de premios de trivia — equivalente a
 * BingoCardPrizePayment, en pgsql_trivia.
 */
class TriviaPrizePayment extends BaseModel
{
    protected $connection = 'pgsql_trivia';

    protected $table = 'trivia_prize_payments';

    public const PAYMENT_STATUSES = [
        'pending'   => 'Pendiente',
        'completed' => 'Completado',
        'failed'    => 'Fallido',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'participant_id',
        'room_id',
        'user_id',
        'currency_id',
        'payment_amount',
        'payment_status',
        'processed_at',
        'transaction_reference',
        'payment_notes',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:8',
        'processed_at'   => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TriviaParticipant::class, 'participant_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }
}
