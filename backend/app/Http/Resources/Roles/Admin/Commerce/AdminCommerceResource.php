<?php

namespace App\Http\Resources\Roles\Admin\Commerce;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminCommerceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->hashed_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'commission' => $this->commission,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'unique_code' => $this->uniqueCode?->rke_code,
            'sellers_count' => $this->sellers_count,

            // Atributo dinámico seteado por AdminCommerceCrudService::show()
            // antes de construir el resource — no una columna real del
            // modelo. Ver nota ahí: additional() no funciona cuando el
            // resource se anida manualmente dentro de response()->json(),
            // así que el dato se pasa como atributo normal del modelo en
            // vez de vía additional().
            'is_default_commerce' => (bool) $this->is_default_commerce,

            // Jerarquía real Comercializadora → Agencia → Vendedor — mismo
            // criterio de campos que CommerceHierarchyService (dashboard
            // SuperAdmin), aplicado a este único comercio.
            'hierarchy' => $this->whenLoaded('subsidiaries', function () {
                $directSellers = $this->sellers->whereNull('subsidiary_id')->values();
                $subsidiarySellersCount = $this->subsidiaries->sum(fn ($s) => $s->sellers->count());

                return [
                    'direct_sellers' => $directSellers->map(fn ($s) => [
                        'id' => (string) $s->id,
                        'name' => $s->name,
                        'phone' => $s->phone,
                        'status' => $s->status,
                        'status_badge' => $s->status_badge,
                    ])->values(),
                    'subsidiaries' => $this->subsidiaries->map(fn ($sub) => [
                        'id' => (string) $sub->id,
                        'name' => $sub->name,
                        'status' => $sub->status,
                        'status_badge' => $sub->status_badge,
                        'sellers' => $sub->sellers->map(fn ($s) => [
                            'id' => (string) $s->id,
                            'name' => $s->name,
                            'phone' => $s->phone,
                            'status' => $s->status,
                            'status_badge' => $s->status_badge,
                        ])->values(),
                    ])->values(),
                    'direct_sellers_count' => $directSellers->count(),
                    'subsidiaries_count' => $this->subsidiaries->count(),
                    'subsidiary_sellers_count' => $subsidiarySellersCount,
                    'total_sellers_count' => $directSellers->count() + $subsidiarySellersCount,
                ];
            }),

            // Cuentas de cobro (Pago Móvil VE / PIX BR) del usuario dueño
            // del comercio — atributo dinámico seteado por
            // AdminCommerceCrudService::show(), mismo patrón que
            // is_default_commerce.
            'directory_accounts' => $this->directory_accounts ?? ['pago_movil' => [], 'pix_keys' => []],

            'country' => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ],

            'user' => [
                'username' => $this->user->username,
                'email' => $this->user->email,
                'time_zone_id' => $this->user->timeZone->id ?? null,
            ],

            // Moneda principal (nivel 1) - debe devolver ID único, no colección
            'currency' => $this->currenciesActive
                ->where('pivot.level', 1)
                ->pluck('id')
                ->first(), // solo la principal

            // Otras monedas (nivel 2) - array plano de IDs
            'other_currencies' => $this->currenciesActive
                ->where('pivot.level', 2)
                ->pluck('id')
                ->values()
                ->all(),

            'allowed_games' => $this->whenLoaded('gamesActive', function () {
                return $this->gamesActive
                    ->pluck('id')
                    ->values()
                    ->all();
            }),

            // Detalle con nombre/símbolo para la ficha de solo lectura — el
            // form de edición usa 'currency'/'other_currencies'/'allowed_games'
            // (solo IDs) arriba, esto es para mostrar, no para el <select>.
            'currencies_detail' => $this->whenLoaded('currenciesActive', function () {
                return $this->currenciesActive->map(fn ($c) => [
                    'id' => $c->id,
                    'symbol' => $c->symbol,
                    'description' => $c->description,
                    'level' => (int) $c->pivot->level,
                ])->values()->all();
            }),
            'games_detail' => $this->whenLoaded('gamesActive', function () {
                return $this->gamesActive->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                ])->values()->all();
            }),
        ];
    }
}
