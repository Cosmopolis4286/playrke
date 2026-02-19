<?php

namespace App\Http\Requests\Games\Bingo\Config;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para guardar la configuración de reglas del bingo.
 *
 * Acepta multipart/form-data con:
 * - rules (texto de reglamento)
 * - image (imagen PNG/JPEG, opcional)
 */
class BingoConfigRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'commerce']);
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'string', 'min:10', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'rules.required' => 'El reglamento es obligatorio.',
            'rules.min'      => 'El reglamento debe tener al menos 10 caracteres.',
            'rules.max'      => 'El reglamento no puede superar los 10.000 caracteres.',
            'image.image'    => 'El archivo debe ser una imagen válida.',
            'image.mimes'    => 'Solo se aceptan imágenes JPEG o PNG.',
            'image.max'      => 'La imagen no puede superar los 2 MB.',
        ];
    }
}
