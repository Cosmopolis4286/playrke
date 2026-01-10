<?php

namespace App\Http\Resources\Users\Roles;

use App\Http\Resources\Currencies\CurrencyResource;
use App\Http\Resources\Games\GameResource;
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
                'code' => $this->country->code,
                'name' => $this->country->name,
            ],

            'user' => [
                'username' => $this->user->username,
                'email' => $this->user->email,
                'time_zone_id' => $this->user->timeZone->id ?? null,
            ],

            'currency' => [
                'id' => $this->currency->id,
                'symbol' => $this->currency->symbol,
                'description' => $this->currency->description,
            ],

            'other_currencies' => CurrencyResource::collection(
                $this->whenLoaded('otherCurrenciesActive')
            ),

            'allowed_games' => GameResource::collection(
                $this->whenLoaded('allowedGamesActive')
            ),
        ];
    }
}
