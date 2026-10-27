<?php

namespace App\Http\Requests\Roles\Admin\Administrator;

use App\Http\Requests\BaseRequest;
use App\Rules\ValidPhoneForCountry;
use Illuminate\Validation\Rules\Password;

/**
 * Request para validar el alta de un nuevo administrador desde
 * /admins (AdministratorController::store).
 *
 * authorize() delega en UserPolicy::createAdministrator — no repite
 * `role:superadmin` aquí a propósito: el middleware de ruta y la Policy son
 * dos capas independientes (ver UserPolicy), y la Policy ya es la fuente de
 * verdad para esta acción.
 */
class AdministratorStoreRequest extends BaseRequest
{
    public function authorize() : bool
    {
        return $this->user()?->can('createAdministrator', \App\Models\User::class) ?? false;
    }

    public function messages() : array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.regex' => 'El nombre de usuario solo puede contener letras, números y guiones bajos.',
            'username.unique' => 'El nombre de usuario no está disponible.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'country_id.required' => 'El país es obligatorio.',
            'country_id.exists' => 'El país seleccionado no es válido.',
        ];
    }

    public function rules() : array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:40', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'country_id' => ['required', 'string', 'exists:countries,id'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:25', new ValidPhoneForCountry(isoCode2: $this->resolvePhoneCountryIso2())],
        ];
    }

    private function resolvePhoneCountryIso2() : ?string
    {
        $countryId = $this->input('country_id');

        return $countryId ? \App\Models\Country::where('id', $countryId)->value('iso_code_2') : null;
    }
}
