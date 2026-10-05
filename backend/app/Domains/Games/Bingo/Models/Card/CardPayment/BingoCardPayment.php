<?php

namespace App\Domains\Games\Bingo\Models\Card\CardPayment;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BingoCardPayment extends BaseModel
{
    /**
     * Comprobantes en disco PRIVADO (storage/app, no servido por la web):
     * solo accesibles vía BingoCardPaymentReviewController::proof().
     */
    public const PROOF_DISK = 'local';
    public const PROOF_DIRECTORY = 'private/receipts/bingo';

    protected $table = 'bingo_card_payments';

    protected $fillable = [
        'payment_method', 'description', 'room_id', 'creator_user_id', 'seller_user_id',
        'user_id', 'external_player_id', 'currency_id', 'card_lot_id',
        'payment_amount', 'payment_proof', 'payment_status', 'processed_at', 'transaction_reference',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'processed_at'   => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function externalPlayer(): BelongsTo
    {
        return $this->belongsTo(ExternalPlayer::class, 'external_player_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function cardLot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    public function scopeByExternalPlayer($query, string $id)
    {
        return $query->where('external_player_id', $id);
    }

    // Estados alineados con el enum real de la columna
    // (PENDING/APPROVED/REJECTED/CANCELLED) — antes COMPLETED/FAILED, que
    // MySQL rechazaba.
    public function markAsCompleted(?string $transactionReference = null): void
    {
        $this->update([
            'payment_status'          => 'APPROVED',
            'processed_at'            => now(),
            'transaction_reference'   => $transactionReference ?? $this->transaction_reference,
        ]);
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'payment_status' => 'REJECTED',
            'processed_at'   => now(),
            'description'    => $reason ? $this->description . ' - Fallo: ' . $reason : $this->description,
        ]);
    }
}
