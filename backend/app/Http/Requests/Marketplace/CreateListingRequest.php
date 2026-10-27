<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class CreateListingRequest extends FormRequest
{
    public function authorize() : bool
    {
        return true;
    }

    public function messages() : array
    {
        return [
            'player_asset_id.required' => 'Selecciona un activo de tu inventario.',
            'quantity.required' => 'Indica la cantidad a publicar.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'price.required' => 'El precio es obligatorio.',
            'price.min' => 'El precio mínimo es 0.01.',
            'currency_id.required' => 'Selecciona una moneda.',
        ];
    }

    public function rules() : array
    {
        return [
            'player_asset_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency_id' => ['required', 'string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
