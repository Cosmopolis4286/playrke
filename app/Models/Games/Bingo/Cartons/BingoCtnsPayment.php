<?php

namespace App\Models\Games\Bingo\Cartons;

use App\Models\Coins\Coin;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\User\Roles\Commerce\Commerce;
use App\Models\User\Roles\Player\External\ExternalPlayers;
use App\Models\User\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Model;

class BingoCtnsPayment extends Model
{
    protected $table = "bingo_ctns_payments"; // plural recomendado en migración

    protected $fillable = [
        'payment',
        'room_id',
        'creator_user_id',
        'seller_id',
        'commerce_id',
        'external_player_id_id',
        'coin_id',
        'lot_id',
        'amount',
        'payment_proof'
    ];

    /**
     * Sala de bingo asociada al pago.
     */
    public function room()
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Comercio asociado al pago.
     */
    public function commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    /**
     * Vendedor asociado al pago.
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Jugador externo (vinculado) asociado al pago.
     */
    public function playerLink()
    {
        return $this->belongsTo(ExternalPlayers::class, 'external_player_id_id');
    }

    /**
     * Moneda usada en el pago.
     */
    public function coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
    }

    /**
     * Lote de cartones adquirido.
     */
    public function ctnLot()
    {
        return $this->belongsTo(BingoCtnsLot::class, 'lot_id');
    }
}
