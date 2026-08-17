<?php

namespace App\Http\Requests\Roles\Admin\User;

use App\Http\Requests\BaseRequest;
use App\Models\Country;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

/**
 * Request para validar la actualización de datos básicos de un Usuario
 * desde la pantalla Admin > Usuarios (AdminUserController::update).
 */
class AdminUserUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix('admin');
    }

    public function rules(): array
    {
        $userId = $this->getId('user');

        return [
            'first_name'   => ['sometimes', 'string', 'max:60'],
            'last_name'    => ['sometimes', 'string', 'max:60'],
            'username'     => ['sometimes', 'string', 'max:20', Rule::unique('users', 'username')->ignore($userId)],
            'email'        => ['sometimes', 'email', 'max:40', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:25', new ValidPhoneForCountry(isoCode2: $this->resolvePhoneCountryIso2())],
            'country_id'   => ['sometimes', 'string', 'exists:countries,id'],
            'role_id'      => ['sometimes', 'string', 'exists:roles,id'],
        ];
    }

    /**
     * ISO-2 del país contra el que validar el formato de phone_number.
     * `country_id` es opcional en este request (el admin puede actualizar
     * solo el teléfono sin tocar el país) — si no viene en el payload, se
     * usa el país que ya tiene el User en base de datos (route model binding).
     */
    private function resolvePhoneCountryIso2(): ?string
    {
        $countryId = $this->input('country_id') ?? $this->route('user')?->country_id;

        return $countryId ? Country::where('id', $countryId)->value('iso_code_2') : null;
    }

    public function messages(): array
    {
        return [
            'username.unique'   => 'El nombre de usuario no está disponible.',
            'email.email'       => 'El correo electrónico debe ser válido.',
            'email.unique'      => 'Este correo ya está registrado.',
            'phone_number.max'  => 'El teléfono no puede superar los 25 caracteres.',
            'country_id.exists' => 'El país seleccionado no es válido.',
            'role_id.exists'    => 'El rol seleccionado no es válido.',
        ];
    }
}
