<?php

namespace App\Http\Requests\ExchangeRate;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Auth\PermissionService;
use App\DTOs\Roles\CommerceDTO;

/**
 * Request para validar datos de una Comercializadora (Commerce).
 *
 * - Se usa tanto para creación como actualización.
 * - Aplica reglas dinámicas según el contexto.
 * - Convierte los datos validados en un DTO.
 */
class ExchangeRateRequest extends FormRequest
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
        $isUpdate   = $this->isUpdate();

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
        return $this->route('commerce') !== null;
    }

    /**
     * Obtiene el ID del comercio si la acción es "update".
     *
     * @return int|null
     */
    private function getCommerceId(): ?int
    {
        return $this->route('commerce')?->id;
    }
}
