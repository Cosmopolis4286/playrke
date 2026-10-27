<?php

namespace App\Http\Requests\Roles\Admin\Commerce;

use App\Http\Requests\BaseRequest;
use App\Models\Roles\Commerce;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

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
     */
    public function authorize() : bool
    {
        // Solo permite usuarios con prefix "admin"
        return PermissionService::checkPrefix('admin');
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages() : array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario no está disponible.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.unique' => 'El número ya está registrado.',
            'name.required' => 'El nombre del comercio es obligatorio.',
            'country.required' => 'El país es obligatorio.',
            'currency.required' => 'La moneda principal es obligatoria.',
            'time_zone.required' => 'La zona horaria es obligatoria.',
            'commission.required' => 'La comisión es obligatoria.',
            'commission.numeric' => 'La comisión debe ser un número.',
            'commission.min' => 'La comisión no puede ser menor a 0.',
            'commission.max' => 'La comisión no puede ser mayor a 100.',
            'commission.regex' => 'La comisión debe tener máximo 2 decimales.',
            'other_currencies.*.exists' => 'Una o más monedas adicionales no son válidas.',
            'allowed_games.required' => 'Debes permitir al menos un juego.',
            'allowed_games.*.exists' => 'Uno o más juegos permitidos no son válidos.',
        ];
    }

    /**
     * Reglas de validación dinámicas.
     *
     * @return array<string, mixed>
     */
    public function rules() : array
    {
        // Resuelve el modelo Commerce manualmente desde el route parameter
        $commerce = $this->resolveCommerce();

        $commerceId = $commerce?->id;
        $isUpdate = $commerce !== null;
        $userId = $commerce?->user_id;

        $rules = [
            'commission' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:100', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'country' => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:countries,id'],
            'currency' => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:currencies,id'],
            'time_zone' => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:time_zones,id'],
            'phone' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:20', new ValidPhoneForCountry($this->input('country'))],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'other_currencies' => ['sometimes', 'array'],
            'other_currencies.*' => ['string', 'exists:currencies,id'],
            'allowed_games' => ['required', 'array'],
            'allowed_games.*' => ['string', 'exists:games,id'],
        ];

        // Regla unique para phone (excluye el comercio actual en update)
        $rules['phone'][] = $commerceId
            ? Rule::unique('commerces', 'phone')->ignore($commerceId)
            : Rule::unique('commerces', 'phone');

        // Reglas para username y email
        if ($isUpdate) {
            // En update: validar solo si se envían, excluyendo el usuario actual
            $rules['username'] = ['sometimes', 'string', 'max:20', Rule::unique('users', 'username')->ignore($userId)];
            $rules['email'] = ['sometimes', 'email', 'max:40', Rule::unique('users', 'email')->ignore($userId)];
        } else {
            // En creación: obligatorios y únicos
            $rules['username'] = ['required', 'string', 'max:20', Rule::unique('users', 'username')];
            $rules['email'] = ['required', 'email', 'max:40', Rule::unique('users', 'email')];
        }

        return $rules;
    }

    /**
     * Resuelve el modelo Commerce desde el parámetro de ruta.
     *
     * El route model binding no está disponible durante la validación,
     * por lo que debemos resolver el modelo manualmente usando el
     * método resolveRouteBinding del modelo.
     */
    protected function resolveCommerce() : ?Commerce
    {
        // Intentar obtener el parámetro por varios nombres posibles
        $routeParam = $this->route('commerce') ?? $this->route('id');

        if ($routeParam === null) {
            return null;
        }

        // Si ya es una instancia de Commerce (binding ya resuelto)
        if ($routeParam instanceof Commerce) {
            return $routeParam;
        }

        // Resolver manualmente usando el método del modelo (soporta Hashids)
        return (new Commerce)->resolveRouteBinding($routeParam);
    }
}
