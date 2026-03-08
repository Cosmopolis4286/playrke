<?php

namespace App\Http\Requests\Games\Bingo\Room;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para compra de cartones desde la vista de salas (game-rooms).
 * Solo accesible por usuarios con rol 'player' / 'customer'.
 */
class BingoRoomPlayerBuyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['customer', 'admin']);
    }

    public function rules(): array
    {
        return [
            'num_ctns'   => ['required', 'integer', 'min:1', 'max:40'],
            'country_id' => ['required', 'string', 'exists:countries,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'num_ctns.required'   => 'Debe indicar la cantidad de cartones.',
            'num_ctns.min'        => 'Debe generar al menos 1 cartón.',
            'num_ctns.max'        => 'No puede generar más de 40 cartones.',
            'country_id.required' => 'Debe seleccionar un país.',
            'country_id.exists'   => 'El país seleccionado no es válido.',
        ];
    }
}
