<?php

namespace App\Http\Resources\Directory;

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
class DirectoryResource extends JsonResource
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
             * Información del tipo de documento.
             */
            'document_type' => [
                'id' => $this->documentType->id ?? null,
                'abbreviation' => $this->documentType->abbreviation ?? null,
                'name' => $this->documentType->name ?? null,
            ],

            /**
             * Número de documento/cédula.
             */
            'document' => $this->document,

            /**
             * Número de teléfono del pago móvil.
             */
            'phone' => $this->phone,

            /**
             * Información del banco venezolano.
             */
            'bank' => [
                'id' => $this->bank->id ?? null,
                'name' => $this->bank->name ?? null,
                'code' => $this->bank->code ?? null,
            ],

            /**
             * Información del usuario propietario (opcional).
             */
            'user' => $this->when($this->user, [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
            ]),

            /**
             * Información formateada completa.
             */
            'formatted_info' => $this->getFormattedInfo(),

            /**
             * Timestamps.
             */
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
