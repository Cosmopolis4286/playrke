<?php

namespace App\DTOs\User\Roles;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Data Transfer Object para transportar la información de una Comercializadora (Commerce).
 *
 * - Convierte automáticamente claves snake_case en propiedades camelCase.
 * - Ignora campos extra que no existan en el DTO.
 * - Soporta instanciación desde arrays validados o FormRequest.
 */
class CommerceDTO
{
    public readonly string $username;
    public readonly string $email;
    public readonly string $country;
    public readonly string $timeZone;
    public readonly string $currency;
    public readonly string $phone;
    public readonly string $name;
    public readonly string $codeRke;
    public readonly int $userRole;
    /** @var array<int,string>|null */
    public readonly ?array $allowedGames;
    /** @var array<int,string>|null */
    public readonly ?array $otherCurrencies;

    /**
     * Constructor privado: mapea snake_case -> camelCase.
     * Ignora campos que no existen en la clase.
     *
     * @param array<string,mixed> $data
     */
    private function __construct(array $data)
    {
        $props = array_keys(get_object_vars($this));

        foreach ($data as $key => $value) {
            $property = self::snakeToCamel($key);

            if (in_array($property, $props, true)) {
                // casteo especial
                $this->$property = $property === 'userRole' ? (int) $value : $value;
            }
        }
    }

    /**
     * Crear DTO desde un array validado.
     *
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Crear DTO desde un FormRequest validado.
     *
     * @param FormRequest $request
     * @return static
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self($request->validated());
    }

    /**
     * Convertir DTO a array asociativo (camelCase -> snake_case).
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $property => $value) {
            $array[self::camelToSnake($property)] = $value;
        }
        return $array;
    }

    /**
     * Convertir snake_case a camelCase.
     */
    private static function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Convertir camelCase a snake_case.
     */
    private static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $string));
    }
}
