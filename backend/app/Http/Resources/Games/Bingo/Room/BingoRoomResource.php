<?php

namespace App\Http\Resources\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BingoRoomResource extends JsonResource
{
    public function toArray($request): array
    {
        $config  = $this->config;
        $userId  = $request->user()?->id;

        return [
            'id'                 => $this->hashed_id,
            'status'             => $this->status->code,
            'can_activate_now'   => $this->resolveCanActivateNow($request, $config, $userId),
            'paid_prizes'        => $this->paid_prizes,
            'prize_pool'         => $this->prize_pool,
            'total_players'      => $this->total_players,
            'total_cards'        => $this->total_cards,
            'total_sales'        => $this->total_sales,
            'is_closed'          => $this->is_closed,
            'total_commissions'  => $this->total_commissions,
            'metrics_by_currency' => $this->metrics_by_currency ?? [],

            'allowed_currencies' => $config?->allowed_currencies ?? [],

            // Monedas con al menos un cartón vendido (BingoCardLot ACTIVE) — el formulario de
            // edición las usa para advertir/impedir que se quiten de "monedas permitidas".
            // Se resuelve con una query distinct ligera (no carga los lotes completos).
            'sold_currencies' => \App\Domains\Games\Bingo\Models\Card\BingoCardLot::where('room_id', $this->id)
                ->where('status', 'ACTIVE')
                ->distinct()
                ->pluck('currency_id')
                ->values(),

            'card_price_by_currency' => $this->whenLoaded('cardPrices', function () {
                return $this->cardPrices->mapWithKeys(fn($p) => [
                    $p->currency_id => $p->card_price
                ]);
            }),

            'rounds_config' => $this->whenLoaded('rounds', function () {
                $prizeTypeMap = ['PCT' => 'percentage', 'FIXED' => 'fixed', 'OTHER' => 'prize'];
                return $this->rounds->map(function ($r) use ($prizeTypeMap) {
                    $prizeType  = $prizeTypeMap[$r->prize_type] ?? 'percentage';
                    $prizes     = $r->relationLoaded('prizes') ? $r->prizes : collect();
                    $pctPrize   = $prizes->first(); // PCT tiene un único registro

                    $entry = [
                        'prizeType'    => $prizeType,
                        'bingoPattern' => $r->bingo_pattern_id,
                        'prizeValue'   => $prizeType === 'percentage' ? (float) ($pctPrize?->prize_value ?? 0) : null,
                    ];

                    if ($prizeType === 'fixed') {
                        $entry['prizeValueByCurrency'] = $prizes
                            ->pluck('prize_value', 'currency_id')
                            ->map(fn($v) => (float) $v)
                            ->toArray();
                    }

                    if ($prizeType === 'prize' && $pctPrize) {
                        $entry['prizeId']    = $pctPrize->prize_id;
                        $entry['prizeName']  = $pctPrize->relationLoaded('prize') ? $pctPrize->prize?->name : null;
                        $entry['prizeValue'] = $pctPrize->relationLoaded('prize') && $pctPrize->prize
                            ? (float) $pctPrize->prize->price
                            : null;
                    }

                    return $entry;
                })->values();
            }),

            'winners' => $this->when(
                $this->relationLoaded('cardPrizes'),
                fn() => $this->cardPrizes->map(fn($cp) => [
                    'id'              => (string) $cp->id,
                    'user_id'         => (string) $cp->creator_user_id,
                    'username'        => $cp->creatorUser?->username,
                    'round_id'        => (string) $cp->round_id,
                    'round_number'    => $cp->round?->round ?? null,
                    'winning_card_id' => (string) $cp->winning_card_id,
                    'serial_number'   => $cp->winningCard?->serial_number ?? null,
                    'winning_pattern' => $cp->winning_pattern,
                    'prize_type'      => $cp->prize_type,
                    'usd_amount'      => (float) $cp->usd_amount,
                    'local_amount'    => (float) $cp->local_amount,
                    'currency_id'     => $cp->currency_id !== null ? (string) $cp->currency_id : null,
                    'prize_status'    => $cp->prize_status,
                    'created_at'      => $cp->created_at?->toISOString(),
                ])->values()
            ),

            'config' => $config ? [
                'room_type_id'            => $config->room_type_id,
                'start_mode'              => $config->start_mode,
                'min_cards_to_start'      => $config->min_cards_to_start,
                'max_players'             => $config->max_players,
                'date'                    => $config->date?->format('Y-m-d'),
                'time'                    => $config->time,
                'game_mode_id'            => $config->game_mode_id,
                'game_variant'            => $config->game_variant ?? 'BINGO_75',
                'prize_pool_type_id'      => $config->prize_pool_type_id,
                'amount_added_prize_pool' => $config->amount_added_prize_pool,
                'room_commission'         => $config->room_commission,
                'rke_commission'          => $config->rke_commission,
                'use_base_usd'            => $config->use_base_usd,
                'card_price_usd'          => $config->card_price_usd,
                'usd_exchange_round_step' => $config->usd_exchange_round_step,
                'jackpot_pct'             => $config->jackpot_pct,
                'time_chip'               => $config->time_chip,
                'rounds'                  => $config->rounds,
                'rounds_pct'              => $config->rounds_pct,
                'use_serials'             => $config->use_serials,
                'serials_pct'             => $config->serials_pct,
                'num_serials'             => $config->num_serials,
                'in_live'                 => $config->in_live,
                'allow_custom_card'       => $config->allow_custom_card,
                'custom_card_price_pct'   => $config->custom_card_price_pct,
                'custom_card_mode'        => $config->custom_card_mode,
                'custom_card_every_x'     => $config->custom_card_every_x,
                'allow_special_card'      => $config->allow_special_card,
                'max_special_cards'       => $config->max_special_cards,
            ] : null,
        ];
    }

    /**
     * Determina si el usuario autenticado puede activar manualmente esta sala:
     * solo aplica a salas sin horario (start_mode = MANUAL), en estado ACTIVE,
     * y solo para el creador de la sala o un admin/superadmin.
     */
    private function resolveCanActivateNow(Request $request, ?BingoRoomConfig $config, mixed $userId): bool
    {
        $user = $request->user();

        if (!$user || !$config || $config->start_mode !== 'MANUAL') {
            return false;
        }

        if ($this->status?->code !== 'ACTIVE') {
            return false;
        }

        $isCreator = (string) $this->creator_user_id === (string) $userId;

        return $isCreator || $user->hasRole(['admin', 'superadmin']);
    }
}
