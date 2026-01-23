<?php

namespace App\Http\Resources\ExchangeRate;

use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'currency' => $this->currency->iso_code,
            'amount' => floatval($this->amount),
            'status' => $this->status,
        ];
    }
}
