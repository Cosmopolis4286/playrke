<?php

namespace App\Http\Requests\Games\Bingo\Card;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar la generación de cartones de Bingo por un vendedor.
 */
class BingoCardGenerateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['seller']);
    }

    public function rules(): array
    {
        return [
            'room_id'          => ['required', 'string'],
            'num_ctns'         => ['required', 'integer', 'min:1', 'max:40'],
            'country_id'       => ['required', 'string', 'exists:countries,id'],
            'player.phone'     => ['required', 'string', 'max:20'],
            'player.name'      => ['required', 'string', 'max:100'],
            'player.last_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'          => 'Debe seleccionar una sala.',
            'num_ctns.required'         => 'Debe indicar la cantidad de cartones.',
            'num_ctns.min'              => 'Debe generar al menos 1 cartón.',
            'num_ctns.max'              => 'No puede generar más de 40 cartones.',
            'country_id.required'       => 'Debe seleccionar un país.',
            'country_id.exists'         => 'El país seleccionado no es válido.',
            'player.phone.required'     => 'El teléfono del jugador es obligatorio.',
            'player.name.required'      => 'El nombre del jugador es obligatorio.',
            'player.last_name.required' => 'El apellido del jugador es obligatorio.',
        ];
    }
}
