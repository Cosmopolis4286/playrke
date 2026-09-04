<?php

namespace App\Http\Requests\Roles\Commerce;

use App\Http\Requests\BaseRequest;
use App\Models\Roles\Commerce;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validación de la autogestión de ficha propia de Commerce — solo los 2
 * campos de bajo riesgo (nombre, teléfono). País/comisión/monedas/juegos
 * no son editables aquí (ver CommerceProfileService::updateOwn()).
 */
class CommerceProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix('commerce');
    }

    public function rules(): array
    {
        $commerce = Commerce::where('user_id', Auth::id())->first();

        return [
            'name'  => ['sometimes', 'string', 'max:30'],
            'phone' => [
                'sometimes', 'string', 'max:14',
                Rule::unique('commerces', 'phone')->ignore($commerce?->id),
                new ValidPhoneForCountry($commerce?->country_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'     => 'El nombre no puede superar los 30 caracteres.',
            'phone.unique' => 'El número ya está registrado.',
        ];
    }
}
