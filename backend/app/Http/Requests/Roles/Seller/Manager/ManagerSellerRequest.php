<?php

namespace App\Http\Requests\Roles\Seller\Manager;

use App\Http\Requests\BaseRequest;
use App\Models\Roles\Seller;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Request para validar datos de un Vendedor (Seller).
 *
 * - Se usa tanto para creación como actualización.
 * - Aplica reglas dinámicas según el contexto.
 */
class ManagerSellerRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     */
    public function authorize() : bool
    {
        // Mismo set de roles que routes/modules/seller.php (middleware de la
        // ruta) y ManagerSellerPolicy::requiredRole() — antes solo admitía
        // 'admin'/'commerce', así que un usuario 'subsidiary' o 'customer'
        // (ambos permitidos por la ruta y la policy) recibía 403 aquí mismo,
        // antes de llegar a la lógica de negocio.
        // 'customer' quitado (ver ManagerSellerPolicy::requiredRole()).
        return PermissionService::checkPrefix(['admin', 'commerce', 'subsidiary']);
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
        // Resuelve el modelo Seller manualmente desde el route parameter
        $seller = $this->resolveSeller();

        $sellerId = $seller?->id;
        $isUpdate = $seller !== null;
        $userId = $seller?->user_id;

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
            // Solo lo usa admin (ver ManagerSellerForm.vue) para elegir a qué
            // Commerce/Subsidiary asignar el seller. Antes solo validaba
            // 'exists:users,id' — cualquier user_id válido pasaba (ej. un
            // treasurer o un seller), y ManagerSellerService::
            // resolveManagerForUser() lo aceptaba igual salvo que el rol
            // fuera 'subsidiary' sin commerce_id, dejando sellers con
            // manager_user_id apuntando a un usuario sin rol de negocio real.
            'manager_user_id' => [
                'sometimes', 'nullable', 'string', 'exists:users,id',
                function(string $attribute, mixed $value, Closure $fail) {
                    if ($value === null) {
                        return;
                    }
                    // role_user (pivot propio del proyecto), no Spatie
                    // model_has_roles — este proyecto asigna roles vía
                    // User::roles() -> belongsToMany(Role::class, 'role_user'),
                    // confirmado contra el pivot real (model_has_roles está
                    // vacío, es infraestructura de Spatie sin usar aquí).
                    $hasValidRole = \Illuminate\Support\Facades\DB::table('role_user')
                        ->join('roles', 'roles.id', '=', 'role_user.role_id')
                        ->where('role_user.user_id', $value)
                        ->whereIn('roles.name', ['commerce', 'subsidiary'])
                        ->exists();
                    if (! $hasValidRole) {
                        $fail('El usuario seleccionado debe tener rol Comercio o Sucursal.');
                    }
                },
            ],
        ];

        // Regla unique para phone (excluye el vendedor actual en update)
        $rules['phone'][] = $sellerId
            ? Rule::unique('sellers', 'phone')->ignore($sellerId)
            : Rule::unique('sellers', 'phone');

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
     * Resuelve el modelo Seller desde el parámetro de ruta.
     *
     * El route model binding no está disponible durante la validación,
     * por lo que debemos resolver el modelo manualmente usando el
     * método resolveRouteBinding del modelo.
     */
    protected function resolveSeller() : ?Seller
    {
        // Intentar obtener el parámetro por varios nombres posibles
        $routeParam = $this->route('seller') ?? $this->route('id');

        if ($routeParam === null) {
            return null;
        }

        // Si ya es una instancia de Seller (binding ya resuelto)
        if ($routeParam instanceof Seller) {
            return $routeParam;
        }

        // Resolver manualmente usando el método del modelo (soporta Hashids)
        return (new Seller)->resolveRouteBinding($routeParam);
    }
}
