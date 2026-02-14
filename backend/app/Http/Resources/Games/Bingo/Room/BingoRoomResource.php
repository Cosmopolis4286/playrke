<?php

namespace App\Http\Resources\Games\Bingo\Room;

use Illuminate\Http\Resources\Json\JsonResource;

class BingoRoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $config  = $this->config->first();
        $creator = $this->whenLoaded('bingoCreators', fn () => $this->bingoCreators->first());

        return [
            'id'                 => $this->hashed_id,
            'status_id'          => $this->status_id,
            'paid_prizes'        => $this->paid_prizes,
            'prize_pool'         => $this->prize_pool,
            'total_players'      => $this->total_players,
            'total_cards'        => $this->total_cards,
            'total_sales'        => $this->total_sales,
            'room_type_description' => $this->room_type_description,
            'is_closed'          => $this->is_closed,
            'total_commissions'  => $this->total_commissions,

            'status' => $this->whenLoaded('status', fn () => [
                'id'          => $this->status->id,
                'value'       => $this->status->value,
                'description' => $this->status->description,
            ]),

            'allowed_currencies' => $creator?->allowed_currencies ?? [],

            'config' => $config ? [
                'room_type'               => $config->room_type,
                'date'                    => $config->date?->format('Y-m-d'),
                'time'                    => $config->time,
                'game_mode_id'            => $config->game_mode_id,
                'prize_pool_type_id'      => $config->prize_pool_type_id,
                'amount_added_prize_pool' => $config->amount_added_prize_pool,
                'card_price'              => $config->card_price,
                'room_commn'              => $config->room_commn,
                'rke_commn'               => $config->rke_commn,
                'jackpot_pct'             => $config->jackpot_pct,
                'time_chip'               => $config->time_chip,
                'rounds'                  => $config->rounds,
                'rounds_pct'              => $config->rounds_pct,
                'serials_pct'             => $config->serials_pct,
                'num_serials'             => $config->num_serials,
                'in_live'                 => $config->in_live,
            ] : null,
        ];
    }
}
