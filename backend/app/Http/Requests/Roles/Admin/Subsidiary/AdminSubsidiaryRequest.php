<?php

namespace App\Http\Requests\Roles\Admin\Subsidiary;

use App\Http\Requests\BaseRequest;
use App\Models\Roles\Subsidiary;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

/**
 * Request para validar datos de una Sucursal (Subsidiary).
 *
 * - Se usa tanto para creación como actualización.
 * - Aplica reglas dinámicas según el contexto.
 */
class AdminSubsidiaryRequest extends BaseRequest
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
        // Resuelve el modelo Subsidiary manualmente desde el route parameter
        $subsidiary = $this->resolveSubsidiary();

        $subsidiaryId = $subsidiary?->id;
        $isUpdate     = $subsidiary !== null;
        $userId       = $subsidiary?->user_id;

        $rules = [
            'name'       => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:30'],
            'commerce'   => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:commerces,id'],
            'country'    => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:countries,id'],
            'currency'   => [$isUpdate ? 'sometimes' : 'required', 'string', 'exists:currencies,id'],
            'phone'      => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:14', new ValidPhoneForCountry($this->input('country'))],
            'commission' => ['sometimes', 'numeric', 'min:0', 'max:100', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'brand'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'product'    => ['sometimes', 'nullable', 'string', 'max:30'],
            'link_route' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        // Regla unique para phone (excluye la sucursal actual en update)
        $rules['phone'][] = $subsidiaryId
            ? Rule::unique('subsidiaries', 'phone')->ignore($subsidiaryId)
            : Rule::unique('subsidiaries', 'phone');

        // Reglas para username y email
        if ($isUpdate) {
            // En update: validar solo si se envían, excluyendo el usuario actual
            $rules['username'] = ['sometimes', 'string', 'max:20', Rule::unique('users', 'username')->ignore($userId)];
            $rules['email']    = ['sometimes', 'email', 'max:40', Rule::unique('users', 'email')->ignore($userId)];
        } else {
            // En creación: obligatorios y únicos
            $rules['username'] = ['required', 'string', 'max:20', Rule::unique('users', 'username')];
            $rules['email']    = ['required', 'email', 'max:40', Rule::unique('users', 'email')];
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
            'name.required'     => 'El nombre de la sucursal es obligatorio.',
            'commerce.required' => 'El comercio al que pertenece la sucursal es obligatorio.',
            'commerce.exists'   => 'El comercio seleccionado no es válido.',
            'country.required'  => 'El país es obligatorio.',
            'currency.required' => 'La moneda es obligatoria.',
            'commission.numeric' => 'La comisión debe ser un número.',
            'commission.min' => 'La comisión no puede ser menor a 0.',
            'commission.max' => 'La comisión no puede ser mayor a 100.',
            'commission.regex' => 'La comisión debe tener máximo 2 decimales.',
        ];
    }

    /**
     * Resuelve el modelo Subsidiary desde el parámetro de ruta.
     *
     * El route model binding no está disponible durante la validación,
     * por lo que debemos resolver el modelo manualmente usando el
     * método resolveRouteBinding del modelo.
     */
    protected function resolveSubsidiary(): ?Subsidiary
    {
        $routeParam = $this->route('subsidiary') ?? $this->route('id');

        if ($routeParam === null) {
            return null;
        }

        if ($routeParam instanceof Subsidiary) {
            return $routeParam;
        }

        return (new Subsidiary())->resolveRouteBinding($routeParam);
    }
}
