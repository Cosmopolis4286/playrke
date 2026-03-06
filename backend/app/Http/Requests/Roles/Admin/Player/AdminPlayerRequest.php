<?php

namespace App\Http\Requests\Roles\Admin\Player;

use App\Http\Requests\BaseRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

/**
 * Request para validar datos de un Jugador (Player).
 *
 * Player es el rol por defecto: opera directamente sobre users.
 * Se usa tanto para creación como actualización.
 */
class AdminPlayerRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix('admin');
    }

    public function rules(): array
    {
        $user     = $this->resolveUser();
        $userId   = $user?->id;
        $isUpdate = $user !== null;

        $rules = [
            'first_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'last_name'  => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'country'    => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:countries,id'],
            'time_zone'  => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:time_zones,id'],
            'phone'      => array_filter([$isUpdate ? 'sometimes' : 'required', 'string', 'max:20', $this->phoneRegex()]),
        ];

        if ($isUpdate) {
            $rules['username'] = ['sometimes', 'string', 'max:20', Rule::unique('users', 'username')->ignore($userId)];
            $rules['email']    = ['sometimes', 'email', 'max:40', Rule::unique('users', 'email')->ignore($userId)];
        } else {
            $rules['username'] = ['required', 'string', 'max:20', Rule::unique('users', 'username')];
            $rules['email']    = ['required', 'email', 'max:40', Rule::unique('users', 'email')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'username.required'  => 'El nombre de usuario es obligatorio.',
            'username.unique'    => 'El nombre de usuario no está disponible.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'El correo electrónico debe ser válido.',
            'email.unique'       => 'Este correo ya está registrado.',
            'first_name.required'=> 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'phone.required'     => 'El teléfono es obligatorio.',
            'phone.regex'        => 'El formato del teléfono no es válido para el país seleccionado.',
            'country.required'   => 'El país es obligatorio.',
            'time_zone.required' => 'La zona horaria es obligatoria.',
        ];
    }

    private const PHONE_PATTERNS = [
        'VE' => '/^\+58 0[2-4]\d{2}-\d{7}$/',
        'BR' => '/^\+55 \(\d{2}\) \d{5}-\d{4}$/',
        'CO' => '/^\+57 3\d{2} \d{3} \d{4}$/',
    ];

    private function phoneRegex(): ?string
    {
        $countryId = $this->input('country');
        if (! $countryId) return null;

        $iso2 = Country::where('id', $countryId)->value('iso_code_2');
        if (! $iso2 || ! isset(self::PHONE_PATTERNS[$iso2])) return null;

        return 'regex:' . self::PHONE_PATTERNS[$iso2];
    }

    /**
     * Resuelve el User desde el parámetro de ruta.
     * Player no tiene tabla propia — trabaja sobre User directamente.
     */
    protected function resolveUser(): ?User
    {
        $routeParam = $this->route('player') ?? $this->route('id');

        if ($routeParam === null) {
            return null;
        }

        if ($routeParam instanceof User) {
            return $routeParam;
        }

        return (new User())->resolveRouteBinding($routeParam);
    }
}
