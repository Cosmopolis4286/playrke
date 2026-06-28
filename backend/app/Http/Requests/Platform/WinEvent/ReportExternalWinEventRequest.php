<?php

namespace App\Http\Requests\Platform\WinEvent;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Http\Requests\BaseRequest;

/**
 * Valida el reporte de una ganancia desde una integración externa
 * (autorización ya resuelta por el middleware publicToken).
 */
class ReportExternalWinEventRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => ['nullable', 'string', 'exists:users,id'],
            'winner_name'       => ['required_without:user_id', 'nullable', 'string', 'max:120'],
            'game_slug'         => ['required', 'string', 'max:50'],
            'game_name'         => ['required', 'string', 'max:100'],
            'prize_kind'        => ['required', 'in:' . implode(',', [
                WinEvent::PRIZE_MONEY, WinEvent::PRIZE_ITEM, WinEvent::PRIZE_SERVICE,
            ])],
            'prize_label'       => ['required_unless:prize_kind,money', 'nullable', 'string', 'max:255'],
            'amount_usd'        => ['required_if:prize_kind,money', 'nullable', 'numeric', 'min:0'],
            'amount_local'      => ['nullable', 'numeric', 'min:0'],
            'currency_id'       => ['nullable', 'string', 'exists:currencies,id'],
            'source_reference'  => ['nullable', 'string', 'max:100'],
            'metadata'          => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'game_slug.required'    => 'Debe indicar el identificador del juego/origen.',
            'game_name.required'    => 'Debe indicar el nombre del juego/origen.',
            'prize_kind.required'   => 'Debe indicar el tipo de premio.',
            'prize_kind.in'         => 'Tipo de premio inválido.',
            'prize_label.required_unless' => 'Debe describir el premio cuando no es monetario.',
            'amount_usd.required_if' => 'Debe indicar el monto en USD para premios monetarios.',
        ];
    }
}
