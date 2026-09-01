<?php

namespace App\Domains\Games\Bingo\Models\Card\CardPrize;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\User;
use App\Services\Games\GamePrizeContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoCardPrize extends BaseModel implements GamePrizeContract
{
    protected $table = 'bingo_card_prizes';

    public const PRIZE_TYPES = [
        'full_house'  => 'Casa Llena',
        'single_line' => 'Línea Simple',
        'serial'      => 'Serial',
    ];

    public const PRIZE_STATUSES = [
        'waiting'    => 'Esperando',
        'processing' => 'Procesando pago',
        'claimed'    => 'Reclamado',
        'cancelled'  => 'Cancelado',
        'suspended'  => 'Suspendido',
        'disputed'   => 'En disputa',
    ];

    protected $fillable = [
        'room_id', 'creator_user_id', 'seller_user_id', 'seller_type', 'room_user_id', 'card_lot_id',
        'winning_card_id', 'currency_id', 'round_id', 'status', 'prize_type',
        'winning_pattern', 'usd_amount', 'local_amount', 'prize_status',
        'claimed_at', 'verification_code', 'prize_notes',
    ];

    protected $casts = [
        'id'                => 'string',
        'status'            => 'string',
        'creator_user_id'   => 'string',
        'seller_user_id'    => 'string',
        'room_user_id'      => 'string',
        'card_lot_id'       => 'string',
        'winning_card_id'   => 'string',
        'currency_id'       => 'string',
        'round_id'          => 'string',
        'usd_amount'        => 'decimal:2',
        'local_amount'      => 'decimal:4',
        'claimed_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    protected $appends = ['prize_type_description', 'prize_status_description'];

    public function room(): BelongsTo { return $this->belongsTo(BingoRoom::class, 'room_id'); }
    public function creatorUser(): BelongsTo { return $this->belongsTo(User::class, 'creator_user_id')->withTrashed(); }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_user_id'); }
    public function roomUser(): BelongsTo { return $this->belongsTo(BingoRoomUser::class, 'room_user_id'); }
    public function cardLot(): BelongsTo { return $this->belongsTo(BingoCardLot::class, 'card_lot_id'); }
    public function winningCard(): BelongsTo { return $this->belongsTo(BingoCard::class, 'winning_card_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class, 'currency_id'); }
    public function round(): BelongsTo { return $this->belongsTo(BingoRound::class, 'round_id'); }

    public function payments(): HasMany
    {
        return $this->hasMany(BingoCardPrizePayment::class, 'card_prize_id');
    }

    public function getPrizeTypeDescriptionAttribute(): string { return self::PRIZE_TYPES[$this->prize_type] ?? $this->prize_type; }
    public function getPrizeStatusDescriptionAttribute(): string { return self::PRIZE_STATUSES[$this->prize_status] ?? $this->prize_status; }

    public function isWaiting(): bool { return $this->prize_status === 'waiting'; }
    public function isProcessing(): bool { return $this->prize_status === 'processing'; }
    public function isClaimed(): bool { return $this->prize_status === 'claimed'; }
    public function isActive(): bool { return $this->status === 'ACTIVE'; }
    public function isCancelled(): bool { return $this->prize_status === 'cancelled'; }
    public function isSuspended(): bool { return $this->prize_status === 'suspended'; }
    public function isDisputed(): bool { return $this->prize_status === 'disputed'; }

    public function markAsDisputed(): bool
    {
        $this->prize_status = 'disputed';
        return $this->save();
    }

    /**
     * Reserva el premio para pago: 'waiting' → 'processing'. Debe llamarse
     * bajo lockForUpdate() y solo si isWaiting() — ver
     * BingoPrizePayoutService::payout(). Una vez en 'processing', cualquier
     * llamada concurrente que obtenga el lock después ve un estado distinto
     * de 'waiting' y no reintenta el pago.
     */
    public function markAsProcessing(): bool
    {
        $this->prize_status = 'processing';
        return $this->save();
    }

    public function markAsClaimed(): bool
    {
        $this->prize_status = 'claimed';
        $this->claimed_at   = now();
        return $this->save();
    }

    public function activate(): bool { $this->status = 'ACTIVE'; return $this->save(); }
    public function deactivate(): bool { $this->status = 'INACTIVE'; return $this->save(); }

    public function generateVerificationCode(): string
    {
        $this->verification_code = strtoupper(bin2hex(random_bytes(4)));
        $this->save();
        return $this->verification_code;
    }

    public function getFormattedUsdAmount(): string { return number_format($this->usd_amount, 2); }
    public function getFormattedLocalAmount(): string { return $this->currency?->formatAmount((float) $this->local_amount, 4) ?? number_format($this->local_amount, 4, ',', '.'); }

    public function scopeActive($query) { return $query->where('status', 'ACTIVE'); }
    public function scopeInactive($query) { return $query->where('status', 'INACTIVE'); }
    public function scopeByStatus($query, string $status) { return $query->where('prize_status', $status); }
    public function scopeByType($query, string $type) { return $query->where('prize_type', $type); }
    public function scopeWaiting($query) { return $query->where('prize_status', 'waiting'); }
    public function scopeProcessing($query) { return $query->where('prize_status', 'processing'); }
    public function scopeClaimed($query) { return $query->where('prize_status', 'claimed'); }
    public function scopeCancelled($query) { return $query->where('prize_status', 'cancelled'); }
    public function scopeSuspended($query) { return $query->where('prize_status', 'suspended'); }
    public function scopeByRoom($query, string $roomId) { return $query->where('room_id', $roomId); }
    public function scopeByCreator($query, string $id) { return $query->where('creator_user_id', $id); }
    public function scopeByRound($query, string $roundId) { return $query->where('round_id', $roundId); }
    public function scopeWithVerificationCode($query) { return $query->whereNotNull('verification_code'); }
    public function scopeWithoutVerificationCode($query) { return $query->whereNull('verification_code'); }

    // ── GamePrizeContract ────────────────────────────────────────────────────

    public function getGameSlug(): string { return 'bingo'; }
    public function getGameId(): string   { return (string) $this->room_id; }
    public function getUserId(): string   { return (string) $this->creator_user_id; }
    public function getCurrencyId(): string { return (string) $this->currency_id; }
    public function getLocalAmount(): float { return (float) $this->local_amount; }

    public function hasCompletedPayment(): bool
    {
        return $this->payments()->where('payment_status', 'completed')->exists();
    }

    public function buildPaymentAttributes(?string $movementId, string $status, ?string $reason): array
    {
        return [
            'card_prize_id'         => $this->id,
            'payment_method'        => 'system_payment',
            'room_id'               => $this->room_id,
            'creator_user_id'       => $this->creator_user_id,
            'seller_user_id'        => $this->seller_user_id,
            'seller_type'           => $this->seller_type,
            'user_id'               => $this->creator_user_id,
            'currency_id'           => $this->currency_id,
            'payment_amount'        => $this->local_amount,
            'payment_proof'         => $movementId ? 'system_auto' : null,
            'payment_status'        => $status,
            'payment_notes'         => $reason,
            'transaction_reference' => $movementId,
            'processed_at'          => $movementId ? now() : null,
        ];
    }

    public function createPayment(array $attributes): void
    {
        BingoCardPrizePayment::create($attributes);
    }
}
