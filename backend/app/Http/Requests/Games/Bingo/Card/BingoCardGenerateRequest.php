<?php

namespace App\Http\Requests\Games\Bingo\Card;

use App\Http\Requests\BaseRequest;
use App\Rules\ValidPhoneForCountry;
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

    /**
     * Inyecta el room_id real desde el route model binding antes de validar.
     * Así el DTO puede recibirlo via validated() sin que el frontend lo envíe en el body.
     */
    protected function prepareForValidation(): void
    {
        $room = $this->route('room');

        if ($room instanceof \App\Domains\Games\Bingo\Models\Room\BingoRoom) {
            $this->merge(['room_id' => (string) $room->id]);
        }
    }

    public function rules(): array
    {
        return [
            'room_id'          => ['required', 'string', 'exists:bingo_rooms,id'],
            'card_count'         => ['required', 'integer', 'min:1', 'max:40'],
            'country_id'       => ['required', 'string', 'exists:countries,id'],
            'player.phone'     => ['required', 'string', 'max:20', new ValidPhoneForCountry($this->input('country_id'))],
            'player.first_name' => ['required', 'string', 'max:100'],
            'player.last_name' => ['required', 'string', 'max:100'],
            'receipt_number'   => ['required', 'string', 'max:100'],
            'receipt_image'    => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'          => 'Debe seleccionar una sala.',
            'room_id.exists'            => 'La sala seleccionada no es válida.',
            'card_count.required'         => 'Debe indicar la cantidad de cartones.',
            'card_count.min'              => 'Debe generar al menos 1 cartón.',
            'card_count.max'              => 'No puede generar más de 40 cartones.',
            'country_id.required'       => 'Debe seleccionar un país.',
            'country_id.exists'         => 'El país seleccionado no es válido.',
            'player.phone.required'     => 'El teléfono del jugador es obligatorio.',
            'player.first_name.required' => 'El nombre del jugador es obligatorio.',
            'player.last_name.required' => 'El apellido del jugador es obligatorio.',
            'receipt_number.required'   => 'El número de comprobante es obligatorio.',
            'receipt_number.max'       => 'El número de comprobante no puede superar los 100 caracteres.',
            'receipt_image.required'    => 'La imagen del comprobante es obligatoria.',
            'receipt_image.image'       => 'El archivo debe ser una imagen.',
            'receipt_image.mimes'       => 'La imagen debe estar en formato JPG o PNG.',
            'receipt_image.max'         => 'La imagen no puede superar los 2 MB.',
        ];
    }
}
