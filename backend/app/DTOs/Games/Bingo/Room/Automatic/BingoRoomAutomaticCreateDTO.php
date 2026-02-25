<?php

namespace App\DTOs\Games\Bingo\Room\Automatic;

/**
 * Data Transfer Object para la creación de una Sala de Bingo Automatica.
 *
 * - Inmutable
 * - Tipado
 * - Sin lógica de negocio
 */
final class BingoRoomAutomaticCreateDTO
{
    public function __construct(
        public readonly string $date,
        public readonly string $time,
        public readonly string $roomType,
        public readonly string|int $gameMode,
        public readonly string|int $prizePoolType,
        public readonly float $cardPrice,
        public readonly array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly float $roomCommission,
        public readonly ?float $jackpotPct,
        public readonly ?float $playsPct,
        public readonly int $timeChip,
        public readonly int $rounds,
        public readonly ?float $roundsPct,
        public readonly float $serialsPct,
        public readonly int $numSerials,
        public readonly ?array $roundsConfig,
        public readonly ?array $cardPriceByCurrency,
        public readonly ?string $description = null,
        public readonly ?array $days = null,
        public readonly bool $isDraft = false,
    ) {}

    public static function fromArray(array $data): self
    {
        // Normalizar allowedCurrencies: array de strings
        $allowedCurrencies = array_map('strval', array_values((array) ($data['allowedCurrencies'] ?? [])));

        // Normalizar cardPriceByCurrency: array de monedas a precios
        $cardPriceByCurrency = is_array($data['cardPriceByCurrency'] ?? null)
            ? array_map(fn($v) => (float)$v, $data['cardPriceByCurrency'])
            : [];

        return new self(
            date: $data['date'],
            time: $data['time'],
            roomType: $data['roomType'] ?? 'PRIVATE',
            gameMode: $data['gameMode'],
            prizePoolType: $data['prizePoolType'],
            cardPrice: (float) ($data['cardPrice'] ?? 1),
            allowedCurrencies: $allowedCurrencies,
            amountAddedPrizePool: isset($data['amountAddedPrizePool']) ? (float) $data['amountAddedPrizePool'] : null,
            roomCommission: (float) ($data['roomCommn'] ?? 20),
            jackpotPct: isset($data['jackpotPct']) ? (float) $data['jackpotPct'] : null,
            playsPct: isset($data['playsPct']) ? (float) $data['playsPct'] : null,
            timeChip: (int) ($data['timeChip'] ?? 5),
            rounds: (int) ($data['rounds'] ?? 1),
            roundsPct: isset($data['roundsPct']) ? (float) $data['roundsPct'] : null,
            serialsPct: (float) ($data['serialsPct'] ?? 5),
            numSerials: (int) ($data['numSerials'] ?? 5),
            roundsConfig: $data['roundsConfig'] ?? null,
            cardPriceByCurrency: $cardPriceByCurrency,
            description: isset($data['description']) ? (string) $data['description'] : null,
            days: isset($data['days']) && is_array($data['days']) ? $data['days'] : null,
            isDraft: (bool) ($data['isDraft'] ?? false),
        );
    }
}
