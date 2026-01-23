<?php

namespace App\Http\Resources\Roles;

use Illuminate\Http\Resources\Json\JsonResource;

class CommerceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,

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
        ];
    }
}
