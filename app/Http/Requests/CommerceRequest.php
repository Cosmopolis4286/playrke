<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Commerce;
use Illuminate\Support\Facades\Auth;

class CommerceRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Ajusta según tu lógica de permisos
        return Auth::user()?->user_type === 2;
    }

    /**
     * Reglas de validación para creación o actualización.
     *
     * @return array
     */
    public function rules(): array
    {
        $action = $this->route('id') ? 'update' : 'create'; // 'update' = actualización, 'create' = creación
        $commerceId = $action === 'update' ? decrypt($this->route('id')) : null;

        $rules = [
            'username' => ['required', 'string', 'max:20'],
            'email'    => ['required', 'string', 'email', 'max:40'],
            'country'  => ['required', 'numeric', 'exists:countries,id'],
            'time_zone' => ['required', 'numeric', 'exists:time_zones,id'],
            'coin'     => ['required', 'numeric', 'exists:coins,id'],
            'name'     => ['required', 'string'],
            'phone'    => ['required', 'string', 'max:20'],
            'other_coins.*' => ['exists:coins,id'],
            'code_rke' => ['required', 'string', function ($attribute, $value, $fail) use ($commerceId) {
                if ($commerceId) {
                    $commerce = Commerce::find($commerceId);
                    if (Commerce::existsValue('code_rke', $value) && $value !== $commerce->code_rke) {
                        $fail('El código RKE ya existe.');
                    }
                } else {
                    if (Commerce::existsValue('code_rke', $value)) {
                        $fail('El código RKE ya existe.');
                    }
                }
            }],
        ];

        // Ajuste de reglas únicas para username, email y phone
        if ($action === 'update' && $commerceId) {
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
     * @return array
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'Nombre de usuario no disponible.',
            'email.unique'    => 'Este correo ya está registrado.',
            'phone.unique'    => 'Este celular (' . $this->phone . ') ya está registrado.',
            'other_coins.*.exists' => 'Uno o más de los otros coins seleccionados no existen en la base de datos.',
        ];
    }

    /**
     * Devuelve solo los datos validados para crear o actualizar el commerce.
     *
     * @return array
     */
    public function validatedData(): array
    {
        return $this->validated();
    }
}
