<?php

namespace App\DTOs\Games\Bingo\Room;

/**
 * Data Transfer Object para la actualización de una Sala de Bingo.
 *
 * - Inmutable
 * - Tipado
 * - Sin lógica de negocio
 */
final class BingoRoomUpdateDTO
{
    public function __construct(
        public readonly ?string $date,
        public readonly ?string $time,
        public readonly ?string $roomType,
        public readonly string|int|null $gameMode,
        public readonly string|int|null $prizePoolType,
        public readonly ?array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly ?float $roomCommission,
        public readonly ?float $jackpotPct,
        public readonly ?int $timeChip,
        public readonly ?int $rounds,
        public readonly ?float $roundsPct,
        public readonly ?string $useSerials,
        public readonly ?float $serialsPct,
        public readonly ?int $numSerials,
        public readonly ?array $roundsConfig,
        public readonly ?array $cardPriceByCurrency,
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
            date: $data['date'] ?? null,
            time: $data['time'] ?? null,
            roomType: $data['roomType'] ?? null,
            gameMode: $data['gameMode'] ?? null,
            prizePoolType: $data['prizePoolType'] ?? null,
            allowedCurrencies: $allowedCurrencies,
            amountAddedPrizePool: isset($data['amountAddedPrizePool'])
                ? (float) $data['amountAddedPrizePool']
                : null,
            roomCommission: isset($data['roomCommn']) ? (float) $data['roomCommn'] : null,
            jackpotPct: isset($data['jackpotPct']) ? (float) $data['jackpotPct'] : null,
            timeChip: isset($data['timeChip']) ? (int) $data['timeChip'] : null,
            rounds: isset($data['rounds']) ? (int) $data['rounds'] : null,
            roundsPct: isset($data['roundsPct']) ? (float) $data['roundsPct'] : null,
            useSerials: $data['serialsPct'],
            serialsPct: isset($data['serialsPct']) ? (float) $data['serialsPct'] : null,
            numSerials: isset($data['numSerials']) ? (int) $data['numSerials'] : null,
            roundsConfig: $data['roundsConfig'] ?? null,
            cardPriceByCurrency: $cardPriceByCurrency,
        );
    }
}
