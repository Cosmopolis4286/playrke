<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'string'],
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'receipt'   => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Selecciona una billetera.',
            'amount.required'    => 'El monto es obligatorio.',
            'amount.numeric'     => 'El monto debe ser un número.',
            'amount.min'         => 'El monto mínimo es 0.01.',
            'amount.max'         => 'El monto máximo es 999,999.99.',
            'receipt.required'   => 'El comprobante de pago es obligatorio.',
            'receipt.mimes'      => 'El comprobante debe ser JPG, PNG o PDF.',
            'receipt.max'        => 'El comprobante no puede superar 5MB.',
        ];
    }
}
