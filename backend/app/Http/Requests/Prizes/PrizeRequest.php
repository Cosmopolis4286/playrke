<?php

namespace App\Http\Requests\Prizes;

use App\Http\Requests\BaseRequest;
use App\Models\Prize;
use App\Services\Auth\PermissionService;
use Illuminate\Validation\Rule;

/**
 * Request para validar datos de un Premio.
 *
 * - Se usa tanto para creación como actualización.
 * - Aplica reglas dinámicas según el contexto.
 */
class PrizeRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     */
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'commerce']);
    }

    /**
     * Reglas de validación dinámicas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $prize    = $this->resolvePrize();
        $prizeId  = $prize?->id;
        $userId   = $prize?->user_id;
        $isUpdate = $prize !== null;

        $codeRule = $isUpdate
            ? Rule::unique('prizes')->where('user_id', $userId)->ignore($prizeId)
            : Rule::unique('prizes')->where(fn ($q) => $q->where('user_id', auth()->id()));

        return [
            'code'        => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:12', 'alpha_num', $codeRule],
            'reward_type' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(['PRODUCT', 'SERVICE'])],
            'name'        => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:60'],
            'kit'         => ['sometimes', 'string', Rule::in(['Y', 'N'])],
            'num_items'   => ['sometimes', 'integer', 'min:1', 'max:127'],
            'kit_items'   => ['sometimes', 'nullable', 'array', 'max:127'],
            'kit_items.*' => ['string'],
            'price'       => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:9999999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['sometimes', 'string', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required'        => 'El código del premio es obligatorio.',
            'code.max'             => 'El código no puede tener más de 12 caracteres.',
            'code.alpha_num'       => 'El código solo puede contener letras y números.',
            'code.unique'          => 'Ya existe un premio con este código.',
            'reward_type.required' => 'El tipo de recompensa es obligatorio.',
            'reward_type.in'       => 'El tipo debe ser PRODUCT o SERVICE.',
            'name.required'        => 'El nombre del premio es obligatorio.',
            'name.max'             => 'El nombre no puede tener más de 60 caracteres.',
            'kit.in'               => 'El campo kit debe ser Y o N.',
            'num_items.integer'    => 'El número de ítems debe ser un entero.',
            'num_items.min'        => 'El número de ítems debe ser al menos 1.',
            'kit_items.max'        => 'Un kit puede tener máximo 127 ítems.',
            'price.required'       => 'El precio es obligatorio.',
            'price.numeric'        => 'El precio debe ser un número.',
            'price.min'            => 'El precio no puede ser negativo.',
            'description.max'      => 'La descripción no puede tener más de 255 caracteres.',
            'status.in'            => 'El estado debe ser ACTIVE o INACTIVE.',
        ];
    }

    /**
     * Resuelve el modelo Prize desde el parámetro de ruta.
     */
    protected function resolvePrize(): ?Prize
    {
        $routeParam = $this->route('prize');

        if ($routeParam === null) {
            return null;
        }

        if ($routeParam instanceof Prize) {
            return $routeParam;
        }

        return (new Prize())->resolveRouteBinding($routeParam);
    }
}
