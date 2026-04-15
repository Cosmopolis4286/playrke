<?php

namespace App\Models\Games\Trivia;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaRoom extends BaseModel
{
    protected $connection = 'pgsql_trivia';
    protected $table = 'trivia_rooms';

    protected $fillable = [
        'code',
        'host_id',
        'title',
        'description',
        'status',
        'max_players',
        'question_count',
        'time_per_question',
        'config',
        'metadata',
        'scheduled_at',
        // Campos de moneda
        'entry_currency_type',  // 'fixed' | 'dynamic'
        'entry_fee',            // Coste de entrada
        'entry_currency_id',    // Moneda fija (si aplica)
        'local_currency_id',    // Moneda local origen (si dynamic)
        'entry_fee_usd',        // Coste convertido a USD
        'prize_type',           // 'fixed' | 'accumulated'
        'prize_amount',         // Bote/premio
        'prize_currency_id',    // Moneda del bote
        'commission_percent',   // Comisión de la sala
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'entry_fee' => 'decimal:8',
        'entry_fee_usd' => 'decimal:8',
        'prize_amount' => 'decimal:8',
        'commission_percent' => 'decimal:2',
        'scheduled_at' => 'datetime',
    ];

    // Relaciones
    public function participants(): HasMany
    {
        return $this->hasMany(TriviaParticipant::class, 'room_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(TriviaGame::class, 'room_id');
    }

    // Helpers para tipos de moneda
    public function isFixedCurrency(): bool
    {
        return $this->entry_currency_type === 'fixed';
    }

    public function isDynamicCurrency(): bool
    {
        return $this->entry_currency_type === 'dynamic';
    }

    public function hasFixedPrize(): bool
    {
        return $this->prize_type === 'fixed';
    }

    public function hasAccumulatedPrize(): bool
    {
        return $this->prize_type === 'accumulated';
    }

    // Calcular bote acumulado (entradas confirmadas menos comisión)
    public function calculateAccumulatedPrize(): float
    {
        if ($this->hasFixedPrize()) {
            return $this->prize_amount;
        }

        $totalPaid = $this->participants()
            ->where('status', 'confirmed')
            ->sum('paid_amount_usd');

        $commission = $totalPaid * ($this->commission_percent / 100);

        return max($this->prize_amount, $totalPaid - $commission);
    }
}
