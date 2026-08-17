<?php

namespace App\Http\Requests\Roles\Admin\Player;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Rules\ValidPhoneForCountry;
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
            'phone'      => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:20', new ValidPhoneForCountry($this->input('country'))],
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
            'country.required'   => 'El país es obligatorio.',
            'time_zone.required' => 'La zona horaria es obligatoria.',
        ];
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
