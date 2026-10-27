<?php

namespace App\Http\Requests\Directories\Fiat\Brazil;

use App\Http\Requests\BaseRequest;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de una Clave PIX (Brasil).
 *
 * Las reglas varían según el tipo de clave (pix_key_type):
 *   CPF    → 11 dígitos numéricos
 *   CNPJ   → 14 dígitos numéricos
 *   EMAIL  → email válido, max 77 chars
 *   PHONE  → formato brasileño +55XXXXXXXXXXX
 *   RANDOM → UUID v4
 *
 * Payload esperado:
 * {
 *   "name": "Mi cuenta principal",
 *   "pix_key_type": "CPF",
 *   "pix_key_value": "12345678901",
 *   "bank_id": "1234567890"
 * }
 */
final class PixKeyRequest extends BaseRequest
{
    public function authorize() : bool
    {
        // Debe reflejar exactamente los roles admitidos por la ruta
        // (routes/modules/payments.php, grupo 'directory') — antes le
        // faltaban 'superadmin', 'treasurer' y 'subsidiary', que sí podían
        // llegar a esta acción vía la ruta pero recibían 403 aquí.
        return PermissionService::checkPrefix([
            'admin', 'superadmin', 'commerce', 'customer', 'seller', 'player', 'treasurer', 'subsidiary',
        ]);
    }

    public function messages() : array
    {
        return [
            'holder_name.max' => 'El nombre no puede exceder 35 caracteres.',
            'pix_key_type.required' => 'El tipo de clave PIX es obligatorio.',
            'pix_key_type.in' => 'Tipo de clave PIX inválido.',
            'pix_key_value.required' => 'La clave PIX es obligatoria.',
            'pix_key_value.regex' => 'Formato de clave PIX inválido para el tipo seleccionado.',
            'pix_key_value.email' => 'El e-mail no tiene un formato válido.',
            'pix_key_value.uuid' => 'La clave aleatoria debe ser un UUID v4 válido.',
            'bank_id.required' => 'El banco es obligatorio.',
            'bank_id.exists' => 'El banco seleccionado no es válido.',
        ];
    }

    public function rules() : array
    {
        $isUpdate = $this->isUpdate(['room']);
        $req = $isUpdate ? 'sometimes' : 'required';

        return [
            'holder_name' => [
                'nullable',
                'string',
                'max:35',
            ],

            'pix_key_type' => [
                $req,
                'string',
                'in:CPF,CNPJ,EMAIL,PHONE,RANDOM',
            ],

            'pix_key_value' => [
                $req,
                'string',
                ...$this->pixValueRules(),
            ],

            'bank_id' => [
                $req,
                'string',
                'exists:banks_brazil,id',
            ],
        ];
    }

    protected function prepareForValidation() : void
    {
        $type = strtoupper((string) $this->input('pix_key_type', ''));

        // Limpiar pix_key_value según el tipo antes de validar
        $value = trim((string) $this->input('pix_key_value', ''));

        if (in_array($type, ['CPF', 'CNPJ'], true)) {
            $value = preg_replace('/\D/', '', $value);
        } elseif ($type === 'EMAIL') {
            $value = strtolower($value);
        } elseif ($type === 'PHONE') {
            $value = preg_replace('/[^\d+]/', '', $value);
        }

        $this->merge([
            'holder_name' => $this->input('holder_name') ? trim((string) $this->input('holder_name')) : null,
            'pix_key_type' => $type ?: null,
            'pix_key_value' => $value ?: null,
            'bank_id' => $this->input('bank_id') ? (string) $this->input('bank_id') : null,
        ]);
    }

    /**
     * Reglas dinámicas para pix_key_value según el tipo seleccionado.
     */
    private function pixValueRules() : array
    {
        return match ($this->input('pix_key_type')) {
            'CPF' => ['regex:/^\d{11}$/'],
            'CNPJ' => ['regex:/^\d{14}$/'],
            'EMAIL' => ['email', 'max:77'],
            'PHONE' => ['regex:/^\+55\d{10,11}$/'],
            'RANDOM' => ['uuid'],
            default => [],
        };
    }
}
