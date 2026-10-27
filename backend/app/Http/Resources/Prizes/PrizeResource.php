<?php

namespace App\Http\Resources\Prizes;

use App\Models\Prize;
use Illuminate\Http\Resources\Json\JsonResource;

class PrizeResource extends JsonResource
{
    public function toArray($request) : array
    {
        $kitItemsData = [];
        if ($this->kit === 'Y' && ! empty($this->kit_items)) {
            $kitItemsData = Prize::whereIn('id', $this->kit_items)
                ->get(['id', 'code', 'name', 'price'])
                ->map(fn ($p) => [
                    'id' => $p->hashed_id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'price' => $p->price,
                ])
                ->values()
                ->toArray();
        }

        return [
            'id' => $this->hashed_id,
            'code' => $this->code,
            'slug' => $this->slug,
            'reward_type' => $this->reward_type,
            'name' => $this->name,
            'kit' => $this->kit,
            'num_items' => $this->num_items,
            'kit_items' => $this->kit_items ?? [],
            'kit_items_data' => $kitItemsData,
            'price' => $this->price,
            'description' => $this->description,
            'status' => $this->status,
            'user_id' => $this->user_id,
        ];
    }
}
