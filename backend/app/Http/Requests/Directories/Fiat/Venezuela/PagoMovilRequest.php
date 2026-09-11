<?php

namespace App\Http\Requests\Directories\Fiat\Venezuela;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de un Directorio de Pago Móvil (Venezuela).
 *
 * Se utiliza tanto para creación como actualización.
 *
 * Payload esperado:
 * {
 *   "holder_name": "Juan Pérez",
 *   "document_type_id": "1234567890",
 *   "document_number": "12345678",
 *   "phone": "04241234567",
 *   "bank_id": "1234567890",
 * }
 */
final class PagoMovilRequest extends BaseRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta acción.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Debe reflejar exactamente los roles admitidos por la ruta
        // (routes/modules/payments.php, grupo 'directory') — antes le
        // faltaban 'superadmin', 'treasurer' y 'subsidiary', que sí podían
        // llegar a esta acción vía la ruta pero recibían 403 aquí.
        return PermissionService::checkPrefix([
            'admin', 'superadmin', 'commerce', 'customer', 'seller', 'player', 'treasurer', 'subsidiary',
        ]);
    }

    /**
     * Prepara los datos antes de la validación.
     * Convierte IDs numéricos a strings para compatibilidad con Snowflake.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'holder_name' => $this->input('holder_name')
                ? trim((string) $this->input('holder_name'))
                : null,
            'document_type_id' => $this->input('document_type_id')
                ? (string) $this->input('document_type_id')
                : null,
            'bank_id' => $this->input('bank_id')
                ? (string) $this->input('bank_id')
                : null,
            'document_number' => $this->input('document_number')
                ? (string) $this->input('document_number')
                : null,
            'phone' => $this->input('phone')
                ? (string) $this->input('phone')
                : null,
        ]);
    }

    /**
     * Reglas de validación para el directorio de Pago Móvil.
     *
     * Valida:
     * - Tipo de documento (V, E, J)
     * - Número de documento/cédula
     * - Teléfono en formato venezolano
     * - Banco venezolano válido
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isUpdate = $this->isUpdate();

        return [
            /**
             * Nombre del titular de la cuenta.
             */
            'holder_name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
            ],

            /**
             * ID del tipo de documento (V, E, J).
             * Debe existir en la tabla document_types.
             */
            'document_type_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'exists:document_types,id',
            ],

            /**
             * Número de documento/cédula (sin letras).
             * Formato: 7-8 dígitos numéricos.
             */
            'document_number' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'regex:/^\d{7,8}$/',
            ],

            /**
             * Teléfono venezolano.
             * Formato permitido: 04XX1234567 o 04XX-1234567
             */
            'phone' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'regex:/^(04|02)\d{2}-?\d{7}$/',
            ],

            /**
             * ID del banco venezolano.
             * Debe existir en la tabla banks_venezuela.
             */
            'bank_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'exists:banks_venezuela,id',
            ],

            /**
             * Código ISO del país (opcional, por defecto VE).
             */
            'country_code' => [
                'sometimes',
                'string',
                'size:2',
                'in:VE',
            ],
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
            'holder_name.required' => 'El nombre del titular es obligatorio.',
            'holder_name.max' => 'El nombre del titular no puede exceder 100 caracteres.',

            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_type_id.exists' => 'El tipo de documento seleccionado no es válido.',

            'document_number.required' => 'El número de documento/cédula es obligatorio.',
            'document_number.regex' => 'El documento debe tener entre 7 y 8 dígitos numéricos.',

            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'El teléfono debe tener formato venezolano (04XX1234567).',

            'bank_id.required' => 'El banco es obligatorio.',
            'bank_id.exists' => 'El banco seleccionado no es válido.',

            'country_code.in' => 'Solo se admite el país Venezuela (VE).',
        ];
    }
}
