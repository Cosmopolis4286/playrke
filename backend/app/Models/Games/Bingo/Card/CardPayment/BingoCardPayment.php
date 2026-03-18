<?php

namespace App\Models\Games\Bingo\Card\CardPayment;

use App\Models\Games\Bingo\Card\CardLot\BingoCardLot;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Currency;
use App\Models\Roles\Seller;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modelo para registrar pagos de cartones de bingo.
 *
 * Registra los pagos realizados por vendedores/comercios
 * cuando generan cartones para jugadores externos.
 */
final class BingoCardPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bingo_card_payments';

    protected $fillable = [
        'payment_method',
        'description',
        'room_id',
        'creator_user_id',
        'seller_id',
        'user_id',
        'external_player_id',
        'currency_id',
        'card_lot_id',
        'payment_amount',
        'payment_proof',
        'payment_status',
        'processed_at',
        'transaction_reference',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Obtener la sala asociada al pago.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Obtener el usuario creador del pago.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Obtener el vendedor asociado al pago.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Obtener el usuario que realizó el pago.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtener el jugador externo asociado al pago.
     */
    public function externalPlayer(): BelongsTo
    {
        return $this->belongsTo(ExternalPlayer::class, 'external_player_id');
    }

    /**
     * Obtener la moneda utilizada en el pago.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Obtener el lote de cartones asociado al pago.
     */
    public function cardLot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    /**
     * Marcar el pago como completado.
     */
    public function markAsCompleted(string $transactionReference = null): void
    {
        $this->update([
            'payment_status' => 'COMPLETED',
            'processed_at' => now(),
            'transaction_reference' => $transactionReference,
        ]);
    }

    /**
     * Marcar el pago como fallido.
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'payment_status' => 'FAILED',
            'processed_at' => now(),
            'description' => $reason ? $this->description . ' - Fallo: ' . $reason : $this->description,
        ]);
    }
}
