<?php

namespace App\Http\Resources\Games\Bingo\Pattern;

use Illuminate\Http\Resources\Json\JsonResource;

class BingoPatternResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->hashed_id ?? $this->id,
            'name'            => $this->name,
            'type_pattern'    => $this->type_pattern,
            'type_description'=> $this->type_description,
            'default_pattern' => $this->default_pattern,
            'boxes'           => $this->boxes,
            'boxes_array'     => $this->boxes_array,
            'boxes_count'     => $this->boxes_count,
            'status'          => $this->status,
            'status_description' => $this->status_description,
            'user_id'         => $this->user_id,
            'created_at'      => $this->created_at?->format('Y-m-d H:i'),
            'updated_at'      => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
