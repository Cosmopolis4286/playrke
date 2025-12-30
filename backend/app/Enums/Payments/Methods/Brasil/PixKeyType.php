<?php

namespace App\Enums\Payments\Brasil;

/**
 * Enum con los tipos de clave PIX permitidos.
 */
enum PixKeyType: int
{
    case CPF = 1;
    case Celular = 2;
    case Email = 3;
    case ChaveAleatoria = 4;
    case CNPJ = 5;

    /**
     * Devuelve el nombre legible de la clave PIX.
     */
    public function label(): string
    {
        return match ($this) {
            self::CPF => 'CPF',
            self::Celular => 'Celular',
            self::Email => 'Email',
            self::ChaveAleatoria => 'Chave aleatória',
            self::CNPJ => 'CNPJ',
        };
    }

    /**
     * Devuelve todas las claves en array asociativo.
     */
    public static function all(): array
    {
        return array_map(
            fn($case) => ['id' => $case->value, 'value' => $case->label()],
            self::cases()
        );
    }
}
