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
    public function authorize() : bool
    {
        return PermissionService::checkPrefix(['seller']);
    }

    public function messages() : array
    {
        return [
            'room_id.required' => 'Debe seleccionar una sala.',
            'room_id.exists' => 'La sala seleccionada no es válida.',
            'card_count.required' => 'Debe indicar la cantidad de cartones.',
            'card_count.min' => 'Debe generar al menos 1 cartón.',
            'card_count.max' => 'No puede generar más de 40 cartones.',
            'country_id.required' => 'Debe seleccionar un país.',
            'country_id.exists' => 'El país seleccionado no es válido.',
            'player.phone.required' => 'El teléfono del jugador es obligatorio.',
            'player.first_name.required' => 'El nombre del jugador es obligatorio.',
            'player.last_name.required' => 'El apellido del jugador es obligatorio.',
            'payment_method.required' => 'Indica cómo pagó el comprador.',
            'payment_method.in' => 'El método de pago no es válido.',
            'receipt_number.required' => 'El número de comprobante es obligatorio para pago móvil o transferencia.',
            'receipt_number.max' => 'El número de comprobante no puede superar los 100 caracteres.',
            'receipt_image.required' => 'La foto del comprobante es obligatoria para pago móvil o transferencia.',
            'receipt_image.image' => 'El archivo debe ser una imagen.',
            'receipt_image.mimes' => 'La imagen debe estar en formato JPG o PNG.',
            'receipt_image.max' => 'La imagen no puede superar los 2 MB.',
            'expected_card_price.required' => 'Falta el precio del cartón. Recarga la página e inténtalo de nuevo.',
        ];
    }

    public function rules() : array
    {
        return [
            'room_id' => ['required', 'string', 'exists:bingo_rooms,id'],
            'card_count' => ['required', 'integer', 'min:1', 'max:40'],
            'country_id' => ['required', 'string', 'exists:countries,id'],
            'player.phone' => ['required', 'string', 'max:20', new ValidPhoneForCountry($this->input('country_id'))],
            'player.first_name' => ['required', 'string', 'max:100'],
            'player.last_name' => ['required', 'string', 'max:100'],
            // Efectivo: sin comprobante (exclude_if lo saca de validated(); la
            // venta igual nace PENDING y el comercio confirma que recibió el
            // dinero). Pago móvil / transferencia: comprobante obligatorio.
            'payment_method' => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Domains\Games\Bingo\DTOs\Card\Generate\BingoCardGenerateDTO::PAYMENT_METHODS)],
            'receipt_number' => ['exclude_if:payment_method,cash', 'required', 'string', 'max:100'],
            'receipt_image' => ['exclude_if:payment_method,cash', 'required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            // Precio unitario que el vendedor vio y cobró — ver
            // BingoCardGenerateService::generate() (rechaza si cambió).
            'expected_card_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Inyecta el room_id real desde el route model binding antes de validar.
     * Así el DTO puede recibirlo via validated() sin que el frontend lo envíe en el body.
     */
    protected function prepareForValidation() : void
    {
        $room = $this->route('room');

        if ($room instanceof \App\Domains\Games\Bingo\Models\Room\BingoRoom) {
            $this->merge(['room_id' => (string) $room->id]);
        }
    }
}
