<?php

namespace App\Http\Requests\Games\Bingo\Config;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para guardar solo el texto del reglamento del bingo.
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
        ];
    }

    public function messages(): array
    {
        return [
            'rules.required' => 'El reglamento es obligatorio.',
            'rules.min'      => 'El reglamento debe tener al menos 10 caracteres.',
            'rules.max'      => 'El reglamento no puede superar los 10.000 caracteres.',
        ];
    }
}
