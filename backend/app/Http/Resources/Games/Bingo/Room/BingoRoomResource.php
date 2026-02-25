<?php

namespace App\Http\Resources\Games\Bingo\Room;

use Illuminate\Http\Resources\Json\JsonResource;

class BingoRoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $config  = $this->config->first();

        return [
            'id'                 => $this->hashed_id,
            'status_id'          => $this->status_id,
            'paid_prizes'        => $this->paid_prizes,
            'prize_pool'         => $this->prize_pool,
            'total_players'      => $this->total_players,
            'total_cards'        => $this->total_cards,
            'total_sales'        => $this->total_sales,
            'is_closed'          => $this->is_closed,
            'total_commissions'  => $this->total_commissions,

            'allowed_currencies' => $config?->allowed_currencies ?? [],

            'card_price_by_currency' => $this->whenLoaded('cardPrices', function () {
                return $this->cardPrices->mapWithKeys(fn($p) => [
                    $p->currency_id => $p->card_price
                ]);
            }),

            'rounds_config' => $this->whenLoaded('rounds', function () {
                $prizeTypeMap = ['PCT' => 'percentage', 'FIXED' => 'fixed', 'OTHER' => 'prize'];
                return $this->rounds->map(function ($r) use ($prizeTypeMap) {
                    $prizeType = $prizeTypeMap[$r->prize_type] ?? 'percentage';
                    $config = [
                        'prizeType'    => $prizeType,
                        'bingoPattern' => $r->bingo_pattern_id,
                        'prizeValue'   => $r->prize_value,
                    ];
                    if ($prizeType === 'fixed' && $r->relationLoaded('prizes') && $r->prizes->isNotEmpty()) {
                        $config['prizeValueByCurrency'] = $r->prizes
                            ->pluck('prize_value', 'currency_id')
                            ->map(fn($v) => (float) $v)
                            ->toArray();
                    }
                    if ($prizeType === 'prize') {
                        $config['prizeId']   = $r->prize_id;
                        $config['prizeName'] = $r->relationLoaded('prize') ? $r->prize?->name : null;
                    }
                    return $config;
                })->values();
            }),

            'config' => $config ? [
                'room_type_id'            => $config->room_type_id,
                'date'                    => $config->date?->format('Y-m-d'),
                'time'                    => $config->time,
                'game_mode_id'            => $config->game_mode_id,
                'prize_pool_type_id'      => $config->prize_pool_type_id,
                'amount_added_prize_pool' => $config->amount_added_prize_pool,
                'room_commission'         => $config->room_commission,
                'rke_commission'          => $config->rke_commission,
                'jackpot_pct'             => $config->jackpot_pct,
                'time_chip'               => $config->time_chip,
                'rounds'                  => $config->rounds,
                'rounds_pct'              => $config->rounds_pct,
                'use_serials'             => $config->use_serials,
                'in_live'                 => $config->in_live,
            ] : null,

            if($config->use_serials==='Y'){
                ['config']='serials_pct'             => $config->serials_pct,
                ['config']='num_serials'             => $config->num_serials,
            }
        ];
    }
}
