<?php

namespace App\Http\Requests\Games\Bingo\Config;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para guardar solo la imagen del reglamento del bingo.
 */
class BingoConfigImageRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'superadmin', 'commerce']);
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'La imagen es obligatoria.',
            'image.image'    => 'El archivo debe ser una imagen válida.',
            'image.mimes'    => 'Solo se aceptan imágenes JPEG o PNG.',
            'image.max'      => 'La imagen no puede superar los 2 MB.',
        ];
    }
}
