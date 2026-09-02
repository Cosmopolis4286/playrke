<?php

namespace App\Http\Resources\Roles\Admin\Subsidiary;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubsidiaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->hashed_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'commission' => $this->commission,
            'brand' => $this->brand,
            'product' => $this->product,
            'link_route' => $this->link_route,

            'commerce' => $this->commerce ? [
                'id' => $this->commerce->id,
                'name' => $this->commerce->name,
            ] : null,

            'country' => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ],

            'user' => [
                'username' => $this->user->username,
                'email' => $this->user->email,
            ],

            'currency' => $this->currency_id,
        ];
    }
}
