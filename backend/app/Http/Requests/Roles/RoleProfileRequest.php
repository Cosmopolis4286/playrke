<?php

namespace App\Http\Requests\Roles;

use App\Http\Controllers\Concerns\ResolvesTargetUserId;
use App\Http\Requests\BaseRequest;
use App\Rules\ValidPhoneForCountry;
use App\Services\Auth\PermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Base de la autogestión de ficha propia (Commerce/Subsidiary/Seller "Mi …")
 * — solo los 2 campos de bajo riesgo (nombre, teléfono). Comisión, país,
 * jerarquía, monedas y juegos nunca se editan aquí (ver *ProfileService::
 * updateOwn()).
 *
 * Corrige 3 bugs que tenían las 3 copias previas de este Request:
 *  - phone max:14, pero los teléfonos se guardan formateados
 *    ("+58 0412-0000001", 16 chars) y el resto del sistema usa max:20 →
 *    guardar la ficha SIN tocar el teléfono fallaba la validación.
 *  - la entidad se resolvía con Auth::id(): en la vista admin
 *    (target_user_id) se tomaba la del admin (inexistente) → el unique no
 *    excluía la ficha editada y ValidPhoneForCountry quedaba sin país.
 *  - authorize() solo aceptaba el prefijo del propio rol → la vista admin
 *    mostraba "Editar" pero el guardado devolvía 403 siempre. El acceso a una
 *    ficha AJENA lo sigue validando AdminProfileAccessService en el servicio
 *    (403 para cualquier no-admin que envíe target_user_id).
 */
abstract class RoleProfileRequest extends BaseRequest
{
    use ResolvesTargetUserId;

    /** @return class-string<Model> Modelo de la ficha (Commerce/Subsidiary/Seller). */
    abstract protected function entityClass(): string;

    /** Prefijo del rol dueño de la ficha ('commerce' | 'subsidiary' | 'seller'). */
    abstract protected function ownerPrefix(): string;

    public function authorize(): bool
    {
        return PermissionService::checkPrefix([$this->ownerPrefix(), 'admin', 'superadmin']);
    }

    protected function profileEntity(): ?Model
    {
        $ownerUserId = $this->resolveTargetUserId($this) ?? (string) Auth::id();

        return ($this->entityClass())::where('user_id', $ownerUserId)->first(['id', 'country_id']);
    }

    public function rules(): array
    {
        $entity = $this->profileEntity();
        $table = (new ($this->entityClass()))->getTable();

        return [
            'name'  => ['sometimes', 'filled', 'string', 'max:30'],
            'phone' => [
                'sometimes', 'filled', 'string', 'max:20',
                Rule::unique($table, 'phone')->ignore($entity?->id),
                new ValidPhoneForCountry($entity?->country_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.filled'  => 'El nombre no puede quedar vacío.',
            'name.max'     => 'El nombre no puede superar los 30 caracteres.',
            'phone.filled' => 'El teléfono no puede quedar vacío.',
            'phone.max'    => 'El teléfono no puede superar los 20 caracteres.',
            'phone.unique' => 'El número ya está registrado.',
        ];
    }
}
