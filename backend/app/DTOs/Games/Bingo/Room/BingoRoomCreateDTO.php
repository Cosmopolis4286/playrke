<?php

namespace App\DTOs\Games\Bingo\Room;

/**
 * Data Transfer Object para la creación de una Sala de Bingo.
 *
 * - Inmutable
 * - Tipado
 * - Sin lógica de negocio
 */
final class BingoRoomCreateDTO
{
    public function __construct(
        public readonly ?string $date,
        public readonly ?string $time,
        public readonly string $startMode,
        public readonly ?int $minCardsToStart,
        public readonly string $roomType,
        public readonly string|int $gameMode,
        public readonly string|int $prizePoolType,
        public readonly array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly float $roomCommission,
        public readonly ?float $jackpotPct,
        public readonly int $timeChip,
        public readonly int $rounds,
        public readonly ?float $roundsPct,
        public readonly bool $useSerials,
        public readonly ?float $serialsPct,
        public readonly ?int $numSerials,
        public readonly ?array $roundsConfig,
        public readonly bool $useBaseUsd,
        public readonly ?float $cardPriceUsd,
        public readonly ?float $usdExchangeRoundStep,
        public readonly ?array $cardPriceByCurrency,
        public readonly bool $activateRoom = false,
        public readonly ?string $fromHouse = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Normalizar allowedCurrencies: array de strings
        $allowedCurrencies = array_map('strval', array_values((array) ($data['allowedCurrencies'] ?? [])));

        // Normalizar cardPriceByCurrency: array de monedas a precios
        $cardPriceByCurrency = is_array($data['cardPriceByCurrency'] ?? null)
            ? array_map(fn($v) => (float)$v, $data['cardPriceByCurrency'])
            : [];

        $useSerials = filter_var($data['useSerials'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return new self(
            date: $data['date'] ?? null,
            time: $data['time'] ?? null,
            startMode: $data['startMode'] ?? 'SCHEDULED',
            minCardsToStart: isset($data['minCardsToStart']) ? (int) $data['minCardsToStart'] : null,
            roomType: $data['roomType'] ?? 'PRIVATE',
            gameMode: $data['gameMode'],
            prizePoolType: $data['prizePoolType'],
            allowedCurrencies: $allowedCurrencies,
            amountAddedPrizePool: isset($data['amountAddedPrizePool']) ? (float) $data['amountAddedPrizePool'] : null,
            roomCommission: (float) ($data['roomCommn'] ?? 20),
            jackpotPct: isset($data['jackpotPct']) ? (float) $data['jackpotPct'] : null,
            timeChip: (int) ($data['timeChip'] ?? 5),
            rounds: (int) ($data['rounds'] ?? 1),
            roundsPct: isset($data['roundsPct']) ? (float) $data['roundsPct'] : null,
            useSerials: $useSerials,
            serialsPct: isset($data['serialsPct']) ? (float) $data['serialsPct'] : null,
            numSerials: isset($data['numSerials']) ? (int) $data['numSerials'] : null,
            roundsConfig: $data['roundsConfig'] ?? null,
            useBaseUsd: (bool) ($data['useBaseUsd'] ?? false),
            cardPriceUsd: isset($data['cardPriceUsd']) ? (float) $data['cardPriceUsd'] : null,
            usdExchangeRoundStep: isset($data['usdExchangeRoundStep']) ? (float) $data['usdExchangeRoundStep'] : null,
            cardPriceByCurrency: $cardPriceByCurrency,
            activateRoom: (bool) ($data['activateRoom'] ?? false),
            fromHouse: isset($data['fromHouse']) ? (string) $data['fromHouse'] : null,
        );
    }
}
