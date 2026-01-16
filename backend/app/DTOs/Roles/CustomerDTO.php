<?php

namespace App\DTOs\Users\Roles;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Data Transfer Object para transportar la información de un Cliente (Customer).
 *
 * - Convierte automáticamente claves snake_case en propiedades camelCase.
 * - Ignora campos extra que no existan en el DTO.
 * - Soporta instanciación desde arrays validados o FormRequest.
 * - Facilita el paso de datos tipados entre capas (Controller -> Service).
 *
 * @package App\DTOs\Users\Roles
 */
class CustomerDTO
{
    /**
     * Nombre de usuario del cliente.
     *
     * @var string
     */
    public readonly string $username;

    /**
     * Correo electrónico del cliente.
     *
     * @var string
     */
    public readonly string $email;

    /**
     * ID del país asociado al cliente.
     *
     * @var string
     */
    public readonly string $country;

    /**
     * ID de la zona horaria del cliente.
     *
     * @var string
     */
    public readonly string $timeZone;

    /**
     * ID de la moneda principal del cliente.
     *
     * @var string
     */
    public readonly string $currency;

    /**
     * Número de teléfono del cliente.
     *
     * @var string
     */
    public readonly string $phone;

    /**
     * Nombre del cliente.
     *
     * @var string
     */
    public readonly string $name;

    /**
     * ID único del cliente (customer_id).
     *
     * @var string
     */
    public readonly string $customerId;

    /**
     * Ruta API del cliente.
     *
     * @var string
     */
    public readonly string $apiRoute;

    /**
     * Llave de conexión única del cliente.
     *
     * @var string
     */
    public readonly string $customerKey;

    /**
     * Juegos permitidos para el cliente.
     *
     * @var array<int,string>|null
     */
    public readonly ?array $allowedGames;

    /**
     * Monedas adicionales que maneja el cliente.
     *
     * @var array<int,string>|null
     */
    public readonly ?array $otherCurrencies;

    /**
     * Logo del cliente (archivo subido).
     *
     * @var mixed|null
     */
    public readonly mixed $logo;

    /**
     * Constructor privado: mapea snake_case -> camelCase.
     * Ignora campos que no existen en la clase.
     *
     * @param array<string,mixed> $data Datos a mapear
     */
    private function __construct(array $data)
    {
        $reflection = new \ReflectionClass($this);
        $props = array_map(
            fn($prop) => $prop->getName(),
            $reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
        );

        foreach ($data as $key => $value) {
            $property = self::snakeToCamel($key);

            if (in_array($property, $props, true)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Crear DTO desde un array validado.
     *
     * @param array<string,mixed> $data Array con los datos validados
     * @return static Instancia del DTO
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Crear DTO desde un FormRequest validado.
     *
     * @param FormRequest $request Request validado
     * @return static Instancia del DTO
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self($request->validated());
    }

    /**
     * Convertir DTO a array asociativo (camelCase -> snake_case).
     *
     * Útil para pasar los datos a Eloquent o almacenar en base de datos.
     *
     * @return array<string,mixed> Array con claves en snake_case
     */
    public function toArray(): array
    {
        $array = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $propertyName = $prop->getName();
            if ($prop->isInitialized($this)) {
                $array[self::camelToSnake($propertyName)] = $prop->getValue($this);
            }
        }

        return $array;
    }

    /**
     * Convertir snake_case a camelCase.
     *
     * Ejemplo: "time_zone" -> "timeZone"
     *
     * @param string $string Cadena en snake_case
     * @return string Cadena en camelCase
     */
    private static function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Convertir camelCase a snake_case.
     *
     * Ejemplo: "timeZone" -> "time_zone"
     *
     * @param string $string Cadena en camelCase
     * @return string Cadena en snake_case
     */
    private static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $string));
    }
}
