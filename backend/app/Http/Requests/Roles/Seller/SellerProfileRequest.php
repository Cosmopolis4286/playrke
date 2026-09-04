<?php

namespace App\Http\Requests\Roles\Seller;

use App\Http\Requests\BaseRequest;
use App\Models\Roles\Seller;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validación de la autogestión de ficha propia de Seller — solo los 2
 * campos de bajo riesgo (nombre, teléfono). País/comisión/manager/agencia
 * no son editables aquí (ver SellerProfileService::updateOwn()).
 */
class SellerProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix('seller');
    }

    public function rules(): array
    {
        $seller = Seller::where('user_id', Auth::id())->first();

        return [
            'name'  => ['sometimes', 'string', 'max:30'],
            'phone' => [
                'sometimes', 'string', 'max:14',
                Rule::unique('sellers', 'phone')->ignore($seller?->id),
                new ValidPhoneForCountry($seller?->country_id),
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
