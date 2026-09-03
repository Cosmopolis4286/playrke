<?php

namespace App\Http\Resources\Roles\Admin\Subsidiary;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubsidiaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->hashed_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'commission' => $this->commission,
            'brand' => $this->brand,
            'product' => $this->product,
            'link_route' => $this->link_route,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'sellers_count' => $this->sellers_count,

            'commerce' => $this->commerce ? [
                'id' => $this->commerce->id,
                'name' => $this->commerce->name,
                'status' => $this->commerce->status,
                'status_badge' => $this->commerce->status_badge,
            ] : null,

            'country' => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ],

            'user' => [
                'username' => $this->user->username,
                'email' => $this->user->email,
            ],

            'currency' => $this->currency_id,

            // Jerarquía: hacia arriba (Commerce padre, ya en 'commerce' arriba)
            // y hacia abajo (Sellers propios de esta Subsidiary) — mismo
            // criterio de campos que AdminCommerceResource::hierarchy() y
            // ManagerSellerResource (jerarquía inversa).
            'hierarchy' => $this->whenLoaded('sellers', function () {
                return [
                    'sellers' => $this->sellers->map(fn ($s) => [
                        'id' => (string) $s->id,
                        'name' => $s->name,
                        'phone' => $s->phone,
                        'status' => $s->status,
                        'status_badge' => $s->status_badge,
                    ])->values(),
                    'sellers_count' => $this->sellers->count(),
                ];
            }),
        ];
    }
}
