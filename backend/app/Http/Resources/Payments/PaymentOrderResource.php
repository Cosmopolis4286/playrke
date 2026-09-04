<?php

namespace App\Http\Resources\Payments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domains\Payments\Models\PaymentOrder
 */
class PaymentOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'status' => $this->status,
            'network' => $this->network,
            'token' => $this->token,
            'deposit_address' => $this->deposit_address,
            'expected_amount' => $this->expected_amount,
            'received_amount' => $this->received_amount,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'detected_at' => $this->detected_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'product' => $this->whenLoaded('paymentProduct', fn () => [
                'code' => $this->paymentProduct->code,
                'name' => $this->paymentProduct->name,
            ]),
        ];
    }
}
