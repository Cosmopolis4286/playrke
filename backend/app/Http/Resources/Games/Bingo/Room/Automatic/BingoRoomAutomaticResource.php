<?php

namespace App\Http\Resources\Games\Bingo\Room\Automatic;

use Illuminate\Http\Resources\Json\JsonResource;

class BingoRoomAutomaticResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->hashed_id,
            'status'      => $this->status,
            'days'        => $this->days ?? [],

            'allowed_currencies' => $this->allowed_currencies ?? [],

            'card_price_by_currency' => $this->whenLoaded('cardPriceAutomatic', function () {
                return $this->cardPriceAutomatic?->card_prices ?? [];
            }, []),

            'rounds_config' => $this->whenLoaded('roundAutomatic', function () {
                $round = $this->roundAutomatic;
                if (!$round) return [];

                $prizeTypeMap  = ['PCT' => 'percentage', 'FIXED' => 'fixed', 'OTHER' => 'prize'];
                $prizeTypes    = $round->prize_types ?? [];
                $patterns      = $round->bingo_patterns ?? [];
                $prizeValues   = $round->prize_values ?? [];

                $config = [];
                foreach ($prizeTypes as $i => $type) {
                    $frontendType = $prizeTypeMap[$type] ?? 'percentage';
                    $entry = [
                        'prizeType'    => $frontendType,
                        'bingoPattern' => $patterns[$i] ?? null,
                    ];

                    if ($frontendType === 'fixed') {
                        $entry['prizeValueByCurrency'] = $prizeValues[$i] ?? null;
                        $entry['prizeValue'] = null;
                    } elseif ($frontendType === 'prize') {
                        $entry['prizeId']    = ($prizeValues[$i] ?? [])['prizeId'] ?? null;
                        $entry['prizeName']  = ($prizeValues[$i] ?? [])['prizeName'] ?? null;
                        $entry['prizeValue'] = null;
                    } else {
                        $entry['prizeValue'] = $prizeValues[$i] ?? null;
                    }

                    $config[] = $entry;
                }

                return $config;
            }, []),

            'config' => [
                'room_type_id'            => (string) $this->room_type_id,
                'date'                    => $this->date?->format('Y-m-d'),
                'time'                    => $this->formatted_time,
                'game_mode_id'            => (string) $this->game_mode_id,
                'prize_pool_type_id'      => (string) $this->prize_pool_type_id,
                'amount_added_prize_pool' => $this->amount_added_prize_pool,
                'room_commission'         => $this->room_commission,
                'rke_commission'          => $this->rke_commission,
                'jackpot_pct'             => $this->jackpot_pct,
                'time_chip'               => $this->time_chip,
                'rounds'                  => $this->rounds,
                'rounds_pct'              => $this->rounds_pct,
                'use_serials'             => $this->use_serials,
                'in_live'                 => $this->in_live,
            ],

            if($this->use_serials==='Y'){
                ['config']='serials_pct'             => $this->serials_pct,
                ['config']='num_serials'             => $this->num_serials,
            }
        ];
    }
}
