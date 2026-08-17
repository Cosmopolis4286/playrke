<?php

namespace App\Http\Requests\Roles\Admin\Administrator;

use App\Http\Requests\BaseRequest;
use App\Models\Country;
use App\Rules\ValidPhoneForCountry;
use Illuminate\Validation\Rule;

/**
 * Request para validar la actualización de datos básicos de un
 * administrador desde /admins (AdministratorController::update).
 *
 * authorize() delega en UserPolicy::editAdministrator, resuelto contra el
 * User del route model binding — a diferencia de manageAdministrator(), SÍ
 * permite auto-edición (un superadmin puede editar su propia ficha), pero
 * exige que el objetivo sea realmente admin/superadmin. El status nunca
 * viaja por este endpoint (ver rules() — no incluye 'status'), así que la
 * auto-edición nunca puede auto-desactivarse.
 */
class AdministratorUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editAdministrator', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->getId('user');

        return [
            'first_name'   => ['sometimes', 'string', 'max:60'],
            'last_name'    => ['sometimes', 'string', 'max:60'],
            'username'     => ['sometimes', 'string', 'min:3', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('users', 'username')->ignore($userId)],
            'email'        => ['sometimes', 'email', 'max:40', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:25', new ValidPhoneForCountry(isoCode2: $this->resolvePhoneCountryIso2())],
            'country_id'   => ['sometimes', 'string', 'exists:countries,id'],
        ];
    }

    private function resolvePhoneCountryIso2(): ?string
    {
        $countryId = $this->input('country_id') ?? $this->route('user')?->country_id;

        return $countryId ? Country::where('id', $countryId)->value('iso_code_2') : null;
    }

    public function messages(): array
    {
        return [
            'username.regex'    => 'El nombre de usuario solo puede contener letras, números y guiones bajos.',
            'username.unique'   => 'El nombre de usuario no está disponible.',
            'email.email'       => 'El correo electrónico debe ser válido.',
            'email.unique'      => 'Este correo ya está registrado.',
            'country_id.exists' => 'El país seleccionado no es válido.',
        ];
    }
}
