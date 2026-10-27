<?php

namespace App\Http\Requests\Roles\Admin\WinEvent;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para registrar manualmente un evento de ganancia desde el panel
 * admin — pensado para premios físicos/objetos/servicios entregados fuera
 * de la plataforma (rifas presenciales, torneos offline, etc).
 */
class AdminWinEventRequest extends BaseRequest
{
    public function authorize() : bool
    {
        return PermissionService::checkPrefix(['admin', 'superadmin']);
    }

    public function messages() : array
    {
        return [
            'winner_name.required_without' => 'Indica el nombre del ganador si no está registrado en la plataforma.',
            'game_slug.required' => 'Debe indicar el identificador del juego/origen.',
            'game_name.required' => 'Debe indicar el nombre del juego/origen.',
            'prize_kind.required' => 'Debe indicar el tipo de premio.',
            'prize_label.required_unless' => 'Debe describir el premio cuando no es monetario.',
            'amount_usd.required_if' => 'Debe indicar el monto en USD para premios monetarios.',
        ];
    }

    public function rules() : array
    {
        return [
            'user_id' => ['nullable', 'string', 'exists:users,id'],
            'winner_name' => ['required_without:user_id', 'nullable', 'string', 'max:120'],
            'game_slug' => ['required', 'string', 'max:50'],
            'game_name' => ['required', 'string', 'max:100'],
            'prize_kind' => ['required', 'in:' . implode(',', [
                WinEvent::PRIZE_MONEY, WinEvent::PRIZE_ITEM, WinEvent::PRIZE_SERVICE,
            ])],
            'prize_label' => ['required_unless:prize_kind,money', 'nullable', 'string', 'max:255'],
            'amount_usd' => ['required_if:prize_kind,money', 'nullable', 'numeric', 'min:0'],
            'amount_local' => ['nullable', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'string', 'exists:currencies,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
