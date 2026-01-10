<?php

namespace App\Http\Resources\Currencies;

use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'description' => $this->description,
        ];
    }
}
