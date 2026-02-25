<?php

namespace App\DTOs\Games\Bingo\Room\Automatic;

/**
 * Data Transfer Object para la actualización de una Sala de Bingo Automatica.
 *
 * - Inmutable
 * - Tipado
 * - Sin lógica de negocio
 */
final class BingoRoomAutomaticUpdateDTO
{
    public function __construct(
        public readonly ?string $time,
        public readonly ?array $days = null,
        public readonly ?string $roomType,
        public readonly string|int|null $gameMode,
        public readonly string|int|null $prizePoolType,
        public readonly ?array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly ?float $roomCommission,
        public readonly ?float $jackpotPct,
        public readonly ?int $timeChip,
        public readonly ?int $rounds,
        public readonly ?string $useSerials,
        public readonly ?float $roundsPct,
        public readonly ?float $serialsPct,
        public readonly ?int $numSerials,
        public readonly ?array $roundsConfig,
        public readonly ?array $cardPriceByCurrency,
        public readonly ?string $description = null,
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
            useSerials: $data['useSerials'],
            serialsPct: isset($data['serialsPct']) ? (float) $data['serialsPct'] : null,
            numSerials: isset($data['numSerials']) ? (int) $data['numSerials'] : null,
            roundsConfig: $data['roundsConfig'] ?? null,
            cardPriceByCurrency: $cardPriceByCurrency,
        );
    }
}
