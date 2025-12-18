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
        dd(123);
        // Solo permite usuarios con prefix "commerce"
        return PermissionService::checkPrefix('commerce');
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
            'currency'         => ['required', 'numeric', 'exists:currencies,id'],
            'phone'        => ['required', 'string', 'max:20'],
            'name'         => ['required', 'string'],
            'rke_code'     => ['required', 'string'],
            'other_currencies.*' => ['exists:currencies,id'],
        ];

        // Reglas de unicidad dinámicas
        if ($this->isUpdate() && $commerceId) {
            $rules['username'][] = 'unique:users,username,' . $commerceId;
            $rules['email'][]    = 'unique:users,email,' . $commerceId;
            $rules['phone'][]    = 'unique:commerces,phone,' . $commerceId;
            $rules['rke_code'][] = 'unique:commerces,rke_code,' . $commerceId;
        } else {
            $rules['username'][] = 'unique:users,username';
            $rules['email'][]    = 'unique:users,email';
            $rules['phone'][]    = 'unique:commerces,phone';
            $rules['rke_code'][] = 'unique:commerces,rke_code';
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
            'username.unique' => 'El nombre de usuario no está disponible.',
            'email.unique'    => 'Este correo ya está registrado.',
            'phone.unique'    => 'El número ' . $this->phone . ' ya está registrado.',
            'rke_code.unique' => 'El código RKE ya existe.',
            'other_currencies.*.exists' => 'Uno o más de los otros currencies seleccionados no existen en la base de datos.',
            'time_zone.exists' => 'La zona horaria seleccionada no existe.',
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
