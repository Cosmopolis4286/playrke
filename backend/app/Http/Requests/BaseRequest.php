<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

abstract class BaseRequest extends FormRequest
{
    /**
     * Obtiene el ID o valor asociado a un parámetro de ruta.
     *
     * Soporta:
     *  - Parámetros escalares: /users/{id}
     *  - Model Binding: /commerce/{commerce}
     *
     * Ejemplos:
     *  - getId('id')
     *  - getId('commerce', 'id')
     *  - getId(['commerce', 'id'])
     *
     * @param string|array<string> $params
     */
    public function getId(string|array $params = 'id', ?string $property = 'id') : int|string|null
    {
        return $this->getRouteParameter($params, $property);
    }

    /**
     * Determina si la acción actual corresponde a una actualización.
     *
     * Se considera "update" si alguno de los parámetros de ruta existe.
     *
     * Ejemplos:
     *  - isUpdate('id')
     *  - isUpdate(['commerce', 'user'])
     *
     * @param string|array<string> $params
     */
    public function isUpdate(string|array $params = 'id') : bool
    {
        return $this->hasRouteParameter($params);
    }

    /**
     * Convierte los datos validados en un DTO.
     *
     * La clase DTO debe implementar un método estático fromArray(array $data).
     *
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     *
     * @throws InvalidArgumentException
     */
    public function toDTO(string $dtoClass) : object
    {
        if (! method_exists($dtoClass, 'fromArray')) {
            throw new InvalidArgumentException(
                sprintf('La clase %s debe implementar el método estático fromArray(array $data).', $dtoClass),
            );
        }

        return $dtoClass::fromArray($this->validated());
    }

    /**
     * Obtiene el valor de un parámetro de ruta.
     *
     * Si el parámetro es un objeto (Route Model Binding),
     * puede extraer una propiedad específica.
     *
     * Ejemplos:
     *  - getRouteParameter('id')
     *  - getRouteParameter('commerce', 'id')
     *  - getRouteParameter(['commerce', 'id'])
     *
     * @param string|array<string> $params
     *
     * @return mixed|null
     */
    protected function getRouteParameter(string|array $params, ?string $property = null) : mixed
    {
        foreach ((array) $params as $param) {
            $value = $this->route($param);

            if ($value === null) {
                continue;
            }

            if ($property !== null && is_object($value)) {
                return $value->{$property} ?? null;
            }

            return $value;
        }

        return null;
    }

    /**
     * Determina si existe alguno de los parámetros de ruta indicados.
     *
     * @param string|array<string> $params
     */
    protected function hasRouteParameter(string|array $params) : bool
    {
        foreach ((array) $params as $param) {
            if ($this->route($param) !== null) {
                return true;
            }
        }

        return false;
    }
}
