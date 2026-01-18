<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Auth\PermissionService;
use App\DTOs\Users\Roles\CustomerDTO;

/**
 * Request para validar datos de un Cliente (Customer).
 *
 * - Se usa tanto para creación como actualización.
 * - Determina dinámicamente las reglas según la acción.
 * - Convierte los datos validados en un DTO para los Services.
 * - Valida campos únicos considerando el contexto (crear/editar).
 *
 * @package App\Http\Requests\Users\Roles
 */
class CustomerRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * Solo permite usuarios con prefix "admin".
     *
     * @return bool True si está autorizado
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix('admin');
    }

    /**
     * Reglas de validación dinámicas.
     *
     * Las reglas cambian según si es una creación o actualización.
     * En modo edición, excluye el ID actual de las validaciones de unicidad.
     *
     * @return array<string, mixed> Reglas de validación
     */
    public function rules(): array
    {
        $customerId = $this->getCustomerId();

        $rules = [
            'username'           => ['required', 'string', 'max:20'],
            'email'              => ['required', 'string', 'email', 'max:40'],
            'country'            => ['required', 'string', 'exists:countries,id'],
            'time_zone'          => ['required', 'string', 'exists:time_zones,id'],
            'currency'           => ['required', 'string', 'exists:currencies,id'],
            'phone'              => ['required', 'string', 'max:20'],
            'name'               => ['required', 'string', 'max:255'],
            'customer_id'        => ['required', 'string', 'max:100'],
            'api_route'          => ['required', 'string', 'max:255'],
            'customer_key'       => ['required', 'string', 'max:255'],
            'logo'               => ['nullable', 'file', 'image', 'max:2048'], // Max 2MB
            'other_currencies.*' => ['string', 'exists:currencies,id'],
            'allowed_games.*'    => ['string', 'exists:games,id'],
        ];

        // Reglas de unicidad dinámicas
        if ($this->isUpdate() && $customerId) {
            $rules['username'][]    = 'unique:users,username,' . $customerId;
            $rules['email'][]       = 'unique:users,email,' . $customerId;
            $rules['phone'][]       = 'unique:customers,phone,' . $customerId;
            $rules['customer_id'][] = 'unique:customers,customer_id,' . $customerId;
        } else {
            $rules['username'][]    = 'unique:users,username';
            $rules['email'][]       = 'unique:users,email';
            $rules['phone'][]       = 'unique:customers,phone';
            $rules['customer_id'][] = 'unique:customers,customer_id';
        }

        return $rules;
    }

    /**
     * Mensajes de error personalizados.
     *
     * Proporciona mensajes en español amigables para el usuario.
     *
     * @return array<string, string> Mensajes de error
     */
    public function messages(): array
    {
        return [
            'username.required'      => 'El nombre de usuario es obligatorio.',
            'username.unique'        => 'El nombre de usuario no está disponible.',
            'email.required'         => 'El correo electrónico es obligatorio.',
            'email.email'            => 'El correo electrónico debe ser válido.',
            'email.unique'           => 'Este correo ya está registrado.',
            'phone.required'         => 'El teléfono es obligatorio.',
            'phone.unique'           => 'El número ' . $this->phone . ' ya está registrado.',
            'name.required'          => 'El nombre del cliente es obligatorio.',
            'customer_id.required'   => 'El ID del cliente es obligatorio.',
            'customer_id.unique'     => 'Este ID de cliente ya está en uso.',
            'api_route.required'     => 'La ruta API es obligatoria.',
            'customer_key.required'  => 'La llave de conexión es obligatoria.',
            'country.required'       => 'El país es obligatorio.',
            'country.exists'         => 'El país seleccionado no es válido.',
            'time_zone.required'     => 'La zona horaria es obligatoria.',
            'time_zone.exists'       => 'La zona horaria seleccionada no existe.',
            'currency.required'      => 'La moneda principal es obligatoria.',
            'currency.exists'        => 'La moneda seleccionada no es válida.',
            'logo.image'             => 'El logo debe ser una imagen válida.',
            'logo.max'               => 'El logo no debe exceder 2MB.',
            'other_currencies.*.exists' => 'Una o más de las monedas adicionales no son válidas.',
            'allowed_games.*.exists'    => 'Uno o más de los juegos seleccionados no son válidos.',
        ];
    }

    /**
     * Convierte los datos validados en un DTO (Data Transfer Object).
     *
     * Esto permite que el Service reciba un objeto estructurado
     * en lugar de arrays sin tipar, mejorando la seguridad de tipos.
     *
     * @return CustomerDTO DTO con los datos validados
     */
    public function toDTO(): CustomerDTO
    {
        return CustomerDTO::fromArray($this->validated());
    }

    /**
     * Determina si la acción actual es una actualización.
     *
     * Verifica si existe un ID en la ruta, lo que indica que es
     * una operación de edición en lugar de creación.
     *
     * @return bool True si es actualización
     */
    private function isUpdate(): bool
    {
        return $this->route('id') !== null;
    }

    /**
     * Obtiene el ID del cliente si la acción es "update".
     *
     * Decodifica el ID hasheado usando Hashids para obtener
     * el ID real de la base de datos.
     *
     * @return string|null ID del cliente o null si es creación
     */
    private function getCustomerId(): ?string
    {
        if (!$this->isUpdate()) {
            return null;
        }

        $decodedIds = \Vinkla\Hashids\Facades\Hashids::connection('main')->decode($this->route('id'));

        return empty($decodedIds) ? null : (string) $decodedIds[0];
    }
}
