<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletWithdrawRequest extends FormRequest
{
    public function authorize() : bool
    {
        return true;
    }

    public function messages() : array
    {
        return [
            'currency_id.required' => 'Selecciona una billetera.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto mínimo es 0.01.',
            'amount.max' => 'El monto máximo es 999,999.99.',
            'directory_id.required' => 'Selecciona un destino de retiro.',
            'directory_type.required' => 'El tipo de directorio es obligatorio.',
            'directory_type.in' => 'Tipo de directorio inválido.',
            'withdrawal_pin.digits' => 'El PIN debe tener exactamente 6 dígitos.',
        ];
    }

    public function rules() : array
    {
        return [
            'currency_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'directory_id' => ['required', 'string'],
            'directory_type' => ['required', 'string', 'in:pago_movil,pix,binance'],
            'notes' => ['nullable', 'string', 'max:500'],
            // 'nullable' aquí (no 'required'): la obligatoriedad real depende
            // de si el usuario ya fijó un PIN (User::hasWithdrawalPin()) y esa
            // decisión vive en WalletService::assertWithdrawalPin(), que da un
            // mensaje distinto ("configura tu PIN") a un usuario sin PIN vs.
            // uno con PIN que lo omitió — exigirlo aquí con 'required' fijo
            // rompería el primer caso con un mensaje genérico de validación.
            'withdrawal_pin' => ['nullable', 'digits:6'],
        ];
    }
}
