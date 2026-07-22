<?php

namespace App\Http\Requests\Roles\Admin\User;

use App\Http\Requests\BaseRequest;
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
            'first_name' => ['sometimes', 'string', 'max:60'],
            'last_name'  => ['sometimes', 'string', 'max:60'],
            'username'   => ['sometimes', 'string', 'max:20', Rule::unique('users', 'username')->ignore($userId)],
            'email'      => ['sometimes', 'email', 'max:40', Rule::unique('users', 'email')->ignore($userId)],
            'country_id' => ['sometimes', 'string', 'exists:countries,id'],
            'role_id'    => ['sometimes', 'string', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'   => 'El nombre de usuario no está disponible.',
            'email.email'       => 'El correo electrónico debe ser válido.',
            'email.unique'      => 'Este correo ya está registrado.',
            'country_id.exists' => 'El país seleccionado no es válido.',
            'role_id.exists'    => 'El rol seleccionado no es válido.',
        ];
    }
}
