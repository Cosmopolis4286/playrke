<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'string', 'exists:wallets,id'],
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Selecciona una billetera.',
            'wallet_id.exists'   => 'La billetera no existe.',
            'amount.required'    => 'El monto es obligatorio.',
            'amount.numeric'     => 'El monto debe ser un número.',
            'amount.min'         => 'El monto mínimo es 0.01.',
            'amount.max'         => 'El monto máximo es 999,999.99.',
        ];
    }
}
