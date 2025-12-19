<?php

namespace App\Http\Requests\User\Roles;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Auth\PermissionService;
use App\DTOs\User\Roles\CommerceDTO;

/**
 * Request para validar datos de una Comercializadora (Commerce).
 *
 * - Se usa tanto para creación como actualización.
 * - Determina dinámicamente las reglas según la acción.
 * - Convierte los datos validados en un DTO para los Services.
 */
class CommerceRequest extends FormRequest
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
        $commerceId = $this->getCommerceId();

        $rules = [
            'username'     => ['required', 'string', 'max:20'],
            'email'        => ['required', 'string', 'email', 'max:40'],
            'country'      => ['required', 'numeric', 'exists:countries,id'],
            'time_zone'    => ['required', 'numeric', 'exists:time_zones,id'],
            'currency'     => ['required', 'integer', 'exists:currencies,id'],
            'phone'        => ['required', 'string', 'max:20'],
            'name'         => ['required', 'string'],
            'other_currencies.*' => ['integer', 'exists:currencies,id'],
        ];

        // Reglas de unicidad dinámicas
        if ($this->isUpdate() && $commerceId) {
            $rules['username'][] = 'unique:users,username,' . $commerceId;
            $rules['email'][]    = 'unique:users,email,' . $commerceId;
            $rules['phone'][]    = 'unique:commerces,phone,' . $commerceId;
        } else {
            $rules['username'][] = 'unique:users,username';
            $rules['email'][]    = 'unique:users,email';
            $rules['phone'][]    = 'unique:commerces,phone';
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
            'phone.unique'      => 'El número ' . $this->phone . ' ya está registrado.',
            'name.required'     => 'El nombre del comercio es obligatorio.',
            'country.required'  => 'El país es obligatorio.',
            'country.exists'    => 'El país seleccionado no es válido.',
            'time_zone.required' => 'La zona horaria es obligatoria.',
            'time_zone.exists'  => 'La zona horaria seleccionada no existe.',
            'currency.required' => 'La moneda principal es obligatoria.',
            'currency.exists'   => 'La moneda seleccionada no es válida. Por favor, selecciona una moneda de la lista.',
            'other_currencies.*.exists' => 'Una o más de las monedas adicionales seleccionadas no son válidas.',
        ];
    }

    /**
     * Convierte los datos validados en un DTO (Data Transfer Object).
     *
     * Esto permite que el Service reciba un objeto estructurado
     * en lugar de arrays sin tipar.
     *
     * @return CommerceDTO
     */
    public function toDTO(): CommerceDTO
    {
        return CommerceDTO::fromArray($this->validated());
    }

    /**
     * Determina si la acción actual es una actualización.
     *
     * @return bool
     */
    private function isUpdate(): bool
    {
        return $this->route('id') !== null;
    }

    /**
     * Obtiene el ID del comercio si la acción es "update".
     *
     * @return int|null
     */
    private function getCommerceId(): ?int
    {
        return $this->isUpdate() ? decrypt($this->route('id')) : null;
    }
}
