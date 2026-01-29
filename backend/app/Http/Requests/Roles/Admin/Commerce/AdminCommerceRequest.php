<?php

namespace App\Http\Requests\Roles\Admin\Commerce;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Comercializadora (Commerce).
 *
 * - Se usa tanto para creación como actualización.
 * - Aplica reglas dinámicas según el contexto.
 */
class AdminCommerceRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Solo permite usuarios con prefix "admin"
        return PermissionService::checkPrefix('admin');
    }

    /**
     * Reglas de validación dinámicas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $commerceId = $this->getId(['commerce', 'id']);
        $isUpdate   = $this->isUpdate(['commerce', 'id']);

        $rules = [
            'country'        => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:countries,id'],
            'currency'       => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:currencies,id'],
            'time_zone'      => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:time_zones,id'],
            'phone'          => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:20'],
            'name'           => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'other_currencies'   => ['sometimes', 'array'],
            'other_currencies.*' => ['string', 'exists:currencies,id'],
            'allowed_games'      => ['sometimes', 'array'],
            'allowed_games.*'    => ['string', 'exists:games,id'],
        ];

        // Regla unique para phone
        $rules['phone'][] = $commerceId
            ? 'unique:commerces,phone,' . $commerceId
            : 'unique:commerces,phone';

        // Solo creación
        if (!$isUpdate) {
            $rules['username'] = ['required', 'string', 'max:20', 'unique:users,username'];
            $rules['email']    = ['required', 'email', 'max:40', 'unique:users,email'];
        }

        return $rules;
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique'   => 'El nombre de usuario no está disponible.',
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'El correo electrónico debe ser válido.',
            'email.unique'      => 'Este correo ya está registrado.',
            'phone.required'    => 'El teléfono es obligatorio.',
            'phone.unique'      => 'El número ya está registrado.',
            'name.required'     => 'El nombre del comercio es obligatorio.',
            'country.required'  => 'El país es obligatorio.',
            'currency.required' => 'La moneda principal es obligatoria.',
            'time_zone.required' => 'La zona horaria es obligatoria.',
            'other_currencies.*.exists' => 'Una o más monedas adicionales no son válidas.',
            'allowed_games.*.exists' => 'Uno o más juegos permitidos no son válidos.',
        ];
    }
}
