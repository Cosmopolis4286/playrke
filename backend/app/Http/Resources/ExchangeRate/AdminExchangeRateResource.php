<?php

namespace App\Http\Resources\ExchangeRate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para tasas de cambio de administradores
 */
class AdminExchangeRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'id' => $this->id,
            'currency_id' => $this->currency_id,
            'amount' => $this->amount,
            'currency' => [
                'id' => $this->currency->id,
                'iso_code' => $this->currency->iso_code,
                'name' => $this->currency->name,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
