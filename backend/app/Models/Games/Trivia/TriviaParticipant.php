<?php

namespace App\Models\Games\Trivia;

use App\Domains\Games\Trivia\Models\TriviaPrizePayment;
use App\Models\BaseModel;
use App\Services\Games\GamePrizeContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriviaParticipant extends BaseModel implements GamePrizeContract
{
    protected $connection = 'pgsql_trivia';
    protected $table = 'trivia_participants';

    public const PRIZE_STATUSES = [
        'waiting'    => 'Esperando',
        'processing' => 'Procesando pago',
        'claimed'    => 'Reclamado',
        'cancelled'  => 'Cancelado',
        'suspended'  => 'Suspendido',
    ];

    protected $fillable = [
        'room_id',
        'team_id',              // null si es free_for_all
        'user_id',
        'status',               // registered, confirmed, playing, eliminated, winner, abandoned
        'registered_at',
        'confirmed_at',
        'paid_amount',
        'paid_currency_id',
        'paid_amount_usd',
        'joined_game_at',
        'eliminated_at',
        'final_rank',
        'prize_won',
        'prize_status',
        'power_ups',
        'power_ups_used',
        'correct_answers',
        'wrong_answers',
        'total_score',
        'total_time',
        'metadata',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:8',
        'paid_amount_usd' => 'decimal:8',
        'prize_won' => 'decimal:8',
        'power_ups' => 'array',
        'power_ups_used' => 'array',
        'total_time' => 'decimal:3',
        'metadata' => 'array',
        'registered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'joined_game_at' => 'datetime',
        'eliminated_at' => 'datetime',
    ];

    // Relaciones
    public function room(): BelongsTo
    {
        return $this->belongsTo(TriviaRoom::class, 'room_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Games\Trivia\Models\TriviaTeam::class, 'team_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TriviaPrizePayment::class, 'participant_id');
    }

    // El usuario está en MySQL, no se puede hacer relación directa Eloquent
    // Se accede mediante user_id manualmente

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'playing']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['eliminated', 'winner', 'abandoned']);
    }

    // Helpers
    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'playing', 'winner']);
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isEliminated(): bool
    {
        return $this->status === 'eliminated';
    }

    public function isWinner(): bool
    {
        return $this->status === 'winner';
    }

    // Comodines disponibles
    public function getAvailablePowerUp(string $type): int
    {
        $powerUps = $this->power_ups ?? [];
        $used = $this->power_ups_used ?? [];

        $total = $powerUps[$type] ?? 0;
        $usedCount = $used[$type] ?? 0;

        return max(0, $total - $usedCount);
    }

    public function usePowerUp(string $type): bool
    {
        if ($this->getAvailablePowerUp($type) <= 0) {
            return false;
        }

        $used = $this->power_ups_used ?? [];
        $used[$type] = ($used[$type] ?? 0) + 1;
        $this->power_ups_used = $used;

        return true;
    }

    // ── GamePrizeContract ────────────────────────────────────────────────────

    public function getGameSlug(): string { return 'trivia'; }
    public function getGameId(): string   { return (string) $this->room_id; }
    public function getUserId(): string   { return (string) $this->user_id; }
    public function getCurrencyId(): string { return (string) $this->room?->prize_currency_id; }
    public function getLocalAmount(): float { return (float) $this->prize_won; }

    public function isWaiting(): bool    { return $this->prize_status === 'waiting'; }
    public function isProcessing(): bool { return $this->prize_status === 'processing'; }
    public function isClaimed(): bool    { return $this->prize_status === 'claimed'; }
    public function isCancelled(): bool  { return $this->prize_status === 'cancelled'; }
    public function isSuspended(): bool  { return $this->prize_status === 'suspended'; }

    public function markAsProcessing(): bool
    {
        $this->prize_status = 'processing';
        return $this->save();
    }

    public function markAsClaimed(): bool
    {
        $this->prize_status = 'claimed';
        return $this->save();
    }

    public function hasCompletedPayment(): bool
    {
        return $this->payments()->where('payment_status', 'completed')->exists();
    }

    public function buildPaymentAttributes(?string $movementId, string $status, ?string $reason): array
    {
        return [
            'participant_id'         => $this->id,
            'room_id'                => $this->room_id,
            'user_id'                => $this->user_id,
            'currency_id'            => $this->getCurrencyId(),
            'payment_amount'         => $this->prize_won,
            'payment_status'         => $status,
            'payment_notes'          => $reason,
            'transaction_reference'  => $movementId,
            'processed_at'           => $movementId ? now() : null,
        ];
    }

    public function createPayment(array $attributes): void
    {
        TriviaPrizePayment::create($attributes);
    }
}
