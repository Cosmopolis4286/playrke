<?php

namespace App\Http\Requests\Roles\Subsidiary;

use App\Http\Requests\BaseRequest;
use App\Models\Roles\Subsidiary;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validación de la autogestión de ficha propia de Subsidiary — solo los 4
 * campos de bajo riesgo (nombre, teléfono, marca, producto). País/moneda/
 * comisión/comercio padre no son editables aquí (ver
 * SubsidiaryProfileService::updateOwn()).
 */
class SubsidiaryProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix('subsidiary');
    }

    public function rules(): array
    {
        $subsidiary = Subsidiary::where('user_id', Auth::id())->first();

        return [
            'name'    => ['sometimes', 'string', 'max:30'],
            'phone'   => [
                'sometimes', 'string', 'max:14',
                Rule::unique('subsidiaries', 'phone')->ignore($subsidiary?->id),
                new ValidPhoneForCountry($subsidiary?->country_id),
            ],
            'brand'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'product' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'    => 'El nombre no puede superar los 30 caracteres.',
            'phone.unique' => 'El número ya está registrado.',
            'brand.max'   => 'La marca no puede superar los 20 caracteres.',
            'product.max' => 'El producto no puede superar los 30 caracteres.',
        ];
    }
}
