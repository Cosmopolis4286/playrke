<?php

namespace App\Domains\Games\Bingo\DTOs\Room;

/**
 * Data Transfer Object para la creación de una Sala de Bingo.
 *
 * - Inmutable
 * - Tipado
 * - Sin lógica de negocio
 */
final class BingoRoomCreateDTO
{
    public static function fromArray(array $data) : self
    {
        // Normalizar allowedCurrencies: array de strings
        $allowedCurrencies = array_map('strval', array_values((array) ($data['allowedCurrencies'] ?? [])));

        // Normalizar cardPriceByCurrency: array de monedas a precios
        $cardPriceByCurrency = is_array($data['cardPriceByCurrency'] ?? null)
            ? array_map(fn ($v) => (float) $v, $data['cardPriceByCurrency'])
            : [];

        $useSerials = filter_var($data['useSerials'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return new self(
            date: $data['date'] ?? null,
            time: $data['time'] ?? null,
            startMode: $data['startMode'] ?? 'SCHEDULED',
            minCardsToStart: isset($data['minCardsToStart']) ? (int) $data['minCardsToStart'] : null,
            roomType: $data['roomType'] ?? 'PRIVATE',
            connectionType: $data['connectionType'],
            gameMode: $data['gameMode'] ?? null,
            prizePoolType: $data['prizePoolType'],
            allowedCurrencies: $allowedCurrencies,
            amountAddedPrizePool: isset($data['amountAddedPrizePool']) ? (float) $data['amountAddedPrizePool'] : null,
            roomCommission: (float) ($data['roomCommn'] ?? 20),
            jackpotPct: isset($data['jackpotPct']) ? (float) $data['jackpotPct'] : null,
            timeChip: (int) ($data['timeChip'] ?? 5),
            roundPauseSeconds: isset($data['roundPauseSeconds']) ? (int) $data['roundPauseSeconds'] : null,
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
            gameVariant: $data['gameVariant'] ?? 'BINGO_75',
            roomsPerCurrency: (bool) ($data['roomsPerCurrency'] ?? false),
            isSplitRoom: (bool) ($data['isSplitRoom'] ?? false),
            allowCustomCard: (bool) ($data['allowCustomCard'] ?? false),
            allowNetworkSales: (bool) ($data['allowNetworkSales'] ?? true),
            customCardPricePct: isset($data['customCardPricePct']) ? (float) $data['customCardPricePct'] : null,
            customCardMode: $data['customCardMode'] ?? null,
            customCardEveryX: isset($data['customCardEveryX']) ? (int) $data['customCardEveryX'] : null,
            moveFreeSpaceMode: $data['moveFreeSpaceMode'] ?? null,
            moveFreeSpacePricePct: isset($data['moveFreeSpacePricePct']) ? (float) $data['moveFreeSpacePricePct'] : null,
            allowSpecialCard: (bool) ($data['allowSpecialCard'] ?? false),
            maxSpecialCards: isset($data['maxSpecialCards']) ? (int) $data['maxSpecialCards'] : null,
            maxPlayers: isset($data['maxPlayers']) ? (int) $data['maxPlayers'] : null,
            maxCardsToStart: isset($data['maxCardsToStart']) ? (int) $data['maxCardsToStart'] : null,
            minPlayers: isset($data['minPlayers']) ? (int) $data['minPlayers'] : null,
            accessCode: $data['accessCode'] ?? null,
            roomPassword: $data['roomPassword'] ?? null,
            hostUserId: $data['hostUserId'] ?? null,
            jackpotType: $data['jackpotType'] ?? null,
        );
    }

    public function __construct(
        public readonly ?string $date,
        public readonly ?string $time,
        public readonly string $startMode,
        public readonly ?int $minCardsToStart,
        public readonly string $roomType,
        public readonly string|int $connectionType,
        public readonly string|int|null $gameMode,
        public readonly string|int $prizePoolType,
        public readonly array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly float $roomCommission,
        public readonly ?float $jackpotPct,
        public readonly int $timeChip,
        public readonly ?int $roundPauseSeconds,
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
        public readonly string $gameVariant = 'BINGO_75',
        public readonly bool $roomsPerCurrency = false,
        public readonly bool $isSplitRoom = false,
        public readonly bool $allowCustomCard = false,
        public readonly bool $allowNetworkSales = true,
        public readonly ?float $customCardPricePct = null,
        public readonly ?string $customCardMode = null,
        public readonly ?int $customCardEveryX = null,
        public readonly ?string $moveFreeSpaceMode = null,
        public readonly ?float $moveFreeSpacePricePct = null,
        public readonly bool $allowSpecialCard = false,
        public readonly ?int $maxSpecialCards = null,
        public readonly ?int $maxPlayers = null,
        public readonly ?int $maxCardsToStart = null,
        public readonly ?int $minPlayers = null,
        public readonly ?string $accessCode = null,
        public readonly ?string $roomPassword = null,
        public readonly string|int|null $hostUserId = null,
        public readonly ?string $jackpotType = null,
    ) {
    }
}
