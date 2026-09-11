<?php

namespace App\Http\Resources\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_key' => $this->section_key,
            'label' => $this->label,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_removable' => $this->is_removable,
        ];
    }
}
