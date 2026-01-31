<?php

namespace App\Http\Resources\Directory\Venezuela;

use Illuminate\Http\Resources\Json\JsonResource;

class DirectoryVENResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'currency_id' => $this->currency->iso_code,
            'amount' => floatval($this->amount),
            'status' => $this->status,
        ];
    }
}
