<?php

namespace App\Http\Resources\Directories\Fiat\Venezuela;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para transformar datos del directorio de Pago Móvil.
 *
 * Formatea la respuesta HTTP para el frontend, incluyendo
 * relaciones (banco, tipo de documento) y datos calculados.
 *
 * @package App\Http\Resources\Directory
 *
 * @property \App\Models\Directories\Fiat\Venezuela\PagoMovil $resource
 */
final class PagoMovilResource extends JsonResource
{
    /**
     * Transforma el modelo en un array para la respuesta HTTP.
     *
     * @param Request $request Petición HTTP actual
     * @return array<string, mixed> Datos formateados
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * ID único del registro (Snowflake).
             */
            'id' => $this->id,

            /**
             * Nombre del titular de la cuenta.
             */
            'holder_name' => $this->holder_name,

            /**
             * Información del tipo de documento.
             */
            'document_type_id' => $this->documentType->id ?? null,

            /**
             * Número de documento/cédula.
             */
            'document_number' => $this->document_number,

            /**
             * Número de teléfono del pago móvil.
             */
            'phone' => $this->phone,

            /**
             * Información del banco venezolano.
             */
            'bank_id' => $this->bank->id ?? null,

            /**
             * Información formateada completa.
             */
            'formatted_info' => $this->getFormattedInfo()
        ];
    }
}
