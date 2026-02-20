<?php

namespace App\Http\Resources\Prizes;

use Illuminate\Http\Resources\Json\JsonResource;

class PrizeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'reward_type' => $this->reward_type,
            'name'        => $this->name,
            'kit'         => $this->kit,
            'num_items'   => $this->num_items,
            'kit_items'   => $this->kit_items ?? [],
            'price'       => $this->price,
            'description' => $this->description,
            'status'      => $this->status,
            'user_id'     => $this->user_id,
        ];
    }
}
