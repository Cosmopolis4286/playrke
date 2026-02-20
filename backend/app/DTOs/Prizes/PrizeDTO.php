<?php

namespace App\DTOs\Prizes;

use Vinkla\Hashids\Facades\Hashids;

/**
 * Data Transfer Object para Premio.
 *
 * - Inmutable
 * - Tipado
 * - Compatible con create y update
 * - Sin lógica de negocio
 */
final class PrizeDTO
{
    public function __construct(
        public readonly ?string $code,
        public readonly ?string $rewardType,
        public readonly ?string $name,
        public readonly ?string $kit,
        public readonly ?int    $numItems,
        public readonly ?array  $kitItems,
        public readonly ?string $price,
        public readonly ?string $description,
        public readonly ?string $status,
    ) {}

    public static function fromArray(array $data): self
    {
        $kit      = $data['kit'] ?? null;
        $kitItems = ($kit === 'Y' && isset($data['kit_items']) && is_array($data['kit_items']))
            ? array_values(array_filter(array_map(
                static function ($hashedId) {
                    $decoded = Hashids::connection('main')->decode((string) $hashedId);
                    return !empty($decoded) ? (string) $decoded[0] : null;
                },
                $data['kit_items']
            )))
            : null;
        $numItems = $kitItems !== null
            ? count($kitItems)
            : (isset($data['num_items']) ? (int) $data['num_items'] : null);

        return new self(
            code:        $data['code'] ?? null,
            rewardType:  $data['reward_type'] ?? null,
            name:        $data['name'] ?? null,
            kit:         $kit,
            numItems:    $numItems,
            kitItems:    $kitItems,
            price:       $data['price'] ?? null,
            description: $data['description'] ?? null,
            status:      $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code'        => $this->code,
            'reward_type' => $this->rewardType,
            'name'        => $this->name,
            'kit'         => $this->kit,
            'num_items'   => $this->numItems,
            'kit_items'   => $this->kitItems,
            'price'       => $this->price,
            'description' => $this->description,
            'status'      => $this->status,
        ], static function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
