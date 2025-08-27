<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Request para la validación de datos de un Commerce.
 *
 * - Se usa tanto para creación como actualización.
 * - Determina dinámicamente las reglas según la acción.
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
        // Ajusta la condición según la lógica de permisos de tu aplicación
        return Auth::user()?->user_type === 2;
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
            'coin' => ['required', 'numeric', 'exists:coins,id'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'email' => ['required', 'string', 'email', 'max:40'],
            'name' => ['required', 'string'],
            'other_coins.*' => ['exists:coins,id'],
            'phone' => ['required', 'string', 'max:20'],
            'username' => ['required', 'string', 'max:20'],
            'time_zone.*' => ['numeric', 'exists:time_zones,id'],
        ];

        // Reglas de unicidad dinámicas para actualización vs creación
        if ($this->isUpdate() && $commerceId) {
            $rules['code_rke'][] = 'unique:commerces,code_rke,' . $commerceId;
            $rules['email'][] = 'unique:users,email,' . $commerceId;
            $rules['phone'][] = 'unique:commerces,phone,' . $commerceId;
            $rules['username'][] = 'unique:users,username,' . $commerceId;
        } else {
            $rules['code_rke'][] = 'unique:users,email';
            $rules['email'][] = 'unique:users,email';
            $rules['phone'][] = 'unique:commerces,phone';
            $rules['username'][] = 'unique:users,username';
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
            'email.unique' => 'Este correo ya está registrado.',
            'other_coins.*.exists' => 'Uno o más de los otros coins seleccionados no existen en la base de datos.',
            'phone.unique' => 'El número ' . $this->phone . ' ya está registrado.',
            'username.unique' => 'El nombre de usuario no está disponible.',
            'time_zone.*.exists' => 'Uno o más de las otras zonas horarias seleccionados no existen en la base de datos.',
        ];
    }

    /**
     * Convierte los datos validados en un DTO (Data Transfer Object).
     *
     * Esto permite que el Service reciba un objeto estructurado
     * en lugar de arrays sin tipar.
     *
     * @return \App\DTOs\CommerceDTO
     */
    public function toDTO()
    {
        return new \App\DTOs\CommerceDTO($this->validated());
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
