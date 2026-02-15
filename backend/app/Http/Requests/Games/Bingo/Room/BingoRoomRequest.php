<?php

namespace App\Http\Requests\Games\Bingo\Room;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Sala de Bingo (BingoRoom).
 *
 * - Se usa para creación y actualización de salas.
 * - Aplica reglas dinámicas según el contexto del formulario.
 */
class BingoRoomRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'commerce', 'customer']);
    }

    /**
     * Reglas de validación dinámicas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date'                    => ['required', 'date', 'after_or_equal:today'],
            'time'                    => ['required', 'date_format:H:i'],
            'roomType'                => ['required', 'string', 'in:PRIVATE,PUBLIC'],
            'gameMode'                => ['required', 'exists:game_modes,id'],
            'prizePoolType'           => ['required', 'exists:prize_pool_types,id'],
            'ctnPrice'                => ['required', 'numeric', 'min:0.35', 'max:50'],
            'allowedCurrencies'       => ['required', 'array', 'min:1'],
            'allowedCurrencies.*'     => ['required', 'exists:currencies,id'],
            'amountAddedPrizePool'    => ['nullable', 'numeric', 'min:20', 'max:10000'],
            'roomCommn'               => ['required', 'numeric', 'min:10', 'max:50'],
            'jackpotPct'              => ['nullable', 'numeric', 'min:1', 'max:15'],
            'timeChip'                => ['required', 'integer', 'min:3', 'max:10'],
            'rounds'                  => ['required', 'integer', 'min:1', 'max:15'],
            'roundsPct'               => ['nullable', 'numeric', 'min:3', 'max:30'],
            'serialsPct'              => ['required', 'numeric', 'min:2', 'max:35'],
            'numSerials'              => ['required', 'integer', 'min:3', 'max:30'],
            'ctnPrice'                => ['required', 'numeric', 'min:0.35', 'max:50'],
            'roundsConfig'            => ['nullable', 'array'],
            'roundsConfig.*.prizeType'    => ['required_with:roundsConfig', 'in:percentage,fixed'],
            'roundsConfig.*.bingoPattern' => ['nullable', 'exists:bingo_patterns,id'],
            'roundsConfig.*.prizeValue'   => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required'               => 'La fecha es obligatoria.',
            'date.after_or_equal'         => 'La fecha debe ser hoy o posterior.',
            'time.required'               => 'La hora es obligatoria.',
            'time.date_format'            => 'La hora debe tener formato HH:mm.',
            'roomType.required'           => 'El tipo de sala es obligatorio.',
            'roomType.in'                 => 'El tipo de sala debe ser PRIVATE o PUBLIC.',
            'gameMode.required'           => 'El modo de juego es obligatorio.',
            'gameMode.exists'             => 'El modo de juego seleccionado no es válido.',
            'prizePoolType.required'      => 'El tipo de bote es obligatorio.',
            'prizePoolType.exists'        => 'El tipo de bote seleccionado no es válido.',
            'ctnPrice.required'           => 'El precio del cartón es obligatorio.',
            'ctnPrice.min'                => 'El precio del cartón no puede ser menor a 0.35.',
            'ctnPrice.max'                => 'El precio del cartón no puede ser mayor a 50.',
            'allowedCurrencies.required'  => 'Debe seleccionar al menos una moneda.',
            'allowedCurrencies.min'       => 'Debe seleccionar al menos una moneda.',
            'roomCommn.required'          => 'La comisión de sala es obligatoria.',
            'roomCommn.min'               => 'La comisión de sala no puede ser menor a 10.',
            'roomCommn.max'               => 'La comisión de sala no puede ser mayor a 50.',
            'timeChip.required'           => 'El tiempo de ficha es obligatorio.',
            'rounds.required'             => 'El número de rondas es obligatorio.',
            'serialsPct.required'         => 'El porcentaje de seriales es obligatorio.',
            'numSerials.required'         => 'El número de seriales es obligatorio.',
            'ctnPrice.required'           => 'El precio del cartón es obligatorio.',
            'ctnPrice.min'                => 'El precio del cartón no puede ser menor a 0.35.',
        ];
    }
}
