<?php

namespace App\Http\Requests\Games\Bingo\Pattern;

use App\Http\Requests\BaseRequest;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Services\Auth\PermissionService;

/**
 * Request para validar datos de un Patrón de Bingo.
 *
 * Campos del modelo:
 *   - name          (string, 15 chars)
 *   - type_pattern  (UNIQUE | MULTIPLE)
 *   - default_pattern (Y | N)
 *   - boxes         (string, números 1-75 separados por ';')
 */
class BingoPatternRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['admin', 'superadmin', 'commerce', 'customer']);
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:30'],
            'type_pattern'    => ['required', 'in:' . implode(',', array_keys(BingoPattern::TYPES))],
            'default_pattern' => ['nullable', 'in:Y,N'],
            'boxes'           => ['required', 'string', 'max:150'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $boxes = $this->input('boxes', '');
            if (empty($boxes)) return;

            $parts = array_filter(explode(';', $boxes));

            foreach ($parts as $box) {
                $box = trim($box);
                // Acepta formato numérico (1-75) o letra+fila (B1-O5)
                if (preg_match('/^[BINGO][1-5]$/i', $box)) {
                    continue;
                }
                if (!is_numeric($box) || (int) $box < 1 || (int) $box > 75) {
                    $v->errors()->add('boxes', "Casilla inválida: '{$box}'. Use formato numérico (1-75) o letra+fila (B1-O5).");
                    return;
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'El nombre del patrón es obligatorio.',
            'name.max'              => 'El nombre no puede superar 30 caracteres.',
            'type_pattern.required' => 'El tipo de patrón es obligatorio.',
            'type_pattern.in'       => 'El tipo de patrón debe ser UNIQUE o MULTIPLE.',
            'boxes.required'        => 'Las casillas del patrón son obligatorias.',
            'boxes.max'             => 'El campo boxes no puede superar 150 caracteres.',
        ];
    }
}
