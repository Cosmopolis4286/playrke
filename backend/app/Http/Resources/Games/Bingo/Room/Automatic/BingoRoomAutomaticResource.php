<?php

namespace App\Http\Resources\Games\Bingo\Room\Automatic;

use Illuminate\Http\Resources\Json\JsonResource;

class BingoRoomAutomaticResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'     => $this->hashed_id,
            'status' => $this->status->code,
            'days'   => $this->days ?? [],

            'allowed_currencies' => $this->allowed_currencies ?? [],

            'card_price_by_currency' => collect($this->getCardPricesConfig()['card_prices'] ?? [])->map(fn($v) => (float) $v)->toArray(),

            'rounds_config' => $this->formatRoundsConfig(),

            'config' => [
                'room_type_id'            => $this->room_type_id,
                'time'                    => $this->formatted_time,
                'game_mode_id'            => $this->game_mode_id,
                'prize_pool_type_id'      => $this->prize_pool_type_id,
                'amount_added_prize_pool' => $this->amount_added_prize_pool,
                'room_commission'         => $this->room_commission,
                'rke_commission'          => $this->rke_commission,
                'use_base_usd'            => (bool) $this->use_base_usd,
                'card_price_usd'          => $this->card_price_usd !== null ? (float) $this->card_price_usd : null,
                'usd_exchange_round_step' => $this->usd_exchange_round_step !== null ? (float) $this->usd_exchange_round_step : null,
                'jackpot_pct'             => $this->jackpot_pct,
                'time_chip'               => $this->time_chip,
                'rounds'                  => $this->rounds,
                'rounds_pct'              => $this->rounds_pct,
                'use_serials'             => (bool) $this->use_serials,
                'serials_pct'             => $this->use_serials ? $this->serials_pct : null,
                'num_serials'             => $this->use_serials ? $this->num_serials : null,
                'in_live'                 => $this->in_live,
                // Nuevos campos de programación avanzada
                'recurrence_type'         => $this->recurrence_type,
                'recurrence_days'         => $this->recurrence_days ?? [],
                'game_time'               => $this->game_time,
                'start_date'              => $this->start_date?->format('Y-m-d'),
                'end_date'                => $this->end_date?->format('Y-m-d'),
                'default_config'          => $this->default_config ?? [],
                'default_card_price'      => $this->default_card_price !== null ? (float) $this->default_card_price : null,
                'default_currency_id'     => $this->default_currency_id,
                'auto_create_room'        => (bool) $this->auto_create_room,
                'max_rooms_per_day'       => $this->max_rooms_per_day,
                'rooms_created'           => (int) $this->rooms_created,
                'last_room_created_at'    => $this->last_room_created_at?->toISOString(),
            ],
        ];
    }

    protected function formatRoundsConfig(): array
    {
        $roundsConfig = $this->getRoundsConfig();
        $prizeTypes   = $roundsConfig['prize_types'] ?? [];
        $patterns     = $roundsConfig['bingo_patterns'] ?? [];
        $prizeValues  = $roundsConfig['prize_values'] ?? [];

        $prizeTypeMap = ['PCT' => 'percentage', 'FIXED' => 'fixed', 'OTHER' => 'prize'];
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
                $entry['prizeId']    = ($prizeValues[$i] ?? [])['prizeId']   ?? null;
                $entry['prizeName']  = ($prizeValues[$i] ?? [])['prizeName'] ?? null;
                $entry['prizeValue'] = null;
            } else {
                $entry['prizeValue'] = isset($prizeValues[$i]) ? (float) $prizeValues[$i] : null;
            }

            $config[] = $entry;
        }

        return $config;
    }
}
