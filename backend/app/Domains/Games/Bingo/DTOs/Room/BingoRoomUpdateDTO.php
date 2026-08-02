<?php

namespace App\Domains\Games\Bingo\DTOs\Room;

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
        public readonly ?string $startMode,
        public readonly ?int $minCardsToStart,
        public readonly ?string $roomType,
        public readonly string|int|null $connectionType,
        public readonly string|int|null $gameMode,
        public readonly string|int|null $prizePoolType,
        public readonly ?array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly ?float $roomCommission,
        public readonly ?float $jackpotPct,
        public readonly ?int $timeChip,
        public readonly ?int $rounds,
        public readonly ?float $roundsPct,
        public readonly ?bool $useSerials,
        public readonly ?float $serialsPct,
        public readonly ?int $numSerials,
        public readonly ?array $roundsConfig,
        public readonly ?bool $useBaseUsd,
        public readonly ?float $cardPriceUsd,
        public readonly ?float $usdExchangeRoundStep,
        public readonly ?array $cardPriceByCurrency,
        public readonly ?bool $activateRoom,
        public readonly ?string $gameVariant = null,
        public readonly ?bool $allowCustomCard = null,
        public readonly ?float $customCardPricePct = null,
        public readonly ?string $customCardMode = null,
        public readonly ?int $customCardEveryX = null,
        public readonly ?string $moveFreeSpaceMode = null,
        public readonly ?float $moveFreeSpacePricePct = null,
        public readonly ?bool $allowSpecialCard = null,
        public readonly ?int $maxSpecialCards = null,
        public readonly ?int $maxPlayers = null,
        public readonly ?string $accessCode = null,
        public readonly ?string $roomPassword = null,
        public readonly string|int|null $hostUserId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // null → clave ausente (no modificar); [] → borrar todas las monedas
        $allowedCurrencies = array_key_exists('allowedCurrencies', $data)
            ? array_map('strval', array_values((array) $data['allowedCurrencies']))
            : null;

        // Normalizar cardPriceByCurrency: array de monedas a precios
        $cardPriceByCurrency = is_array($data['cardPriceByCurrency'] ?? null)
            ? array_map(fn($v) => (float)$v, $data['cardPriceByCurrency'])
            : [];

        $useBaseUsd = isset($data['useBaseUsd'])
            ? filter_var($data['useBaseUsd'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $useSerials = isset($data['useSerials'])
            ? filter_var($data['useSerials'], FILTER_VALIDATE_BOOLEAN)
            : null;

        return new self(
            date: $data['date'] ?? null,
            time: $data['time'] ?? null,
            startMode: $data['startMode'] ?? null,
            minCardsToStart: array_key_exists('minCardsToStart', $data)
                ? (isset($data['minCardsToStart']) ? (int) $data['minCardsToStart'] : null)
                : null,
            roomType: $data['roomType'] ?? null,
            connectionType: $data['connectionType'] ?? null,
            gameMode: array_key_exists('gameMode', $data) ? $data['gameMode'] : null,
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
            useSerials: $useSerials,
            serialsPct: isset($data['serialsPct']) ? (float) $data['serialsPct'] : null,
            numSerials: isset($data['numSerials']) ? (int) $data['numSerials'] : null,
            roundsConfig: $data['roundsConfig'] ?? null,
            useBaseUsd: $useBaseUsd,
            cardPriceUsd: isset($data['cardPriceUsd']) ? (float) $data['cardPriceUsd'] : null,
            usdExchangeRoundStep: isset($data['usdExchangeRoundStep']) ? (float) $data['usdExchangeRoundStep'] : null,
            cardPriceByCurrency: $cardPriceByCurrency,
            activateRoom: isset($data['activateRoom']) ? (bool) $data['activateRoom'] : null,
            gameVariant: $data['gameVariant'] ?? null,
            allowCustomCard: isset($data['allowCustomCard']) ? (bool) $data['allowCustomCard'] : null,
            customCardPricePct: isset($data['customCardPricePct']) ? (float) $data['customCardPricePct'] : null,
            customCardMode: $data['customCardMode'] ?? null,
            customCardEveryX: isset($data['customCardEveryX']) ? (int) $data['customCardEveryX'] : null,
            moveFreeSpaceMode: $data['moveFreeSpaceMode'] ?? null,
            moveFreeSpacePricePct: isset($data['moveFreeSpacePricePct']) ? (float) $data['moveFreeSpacePricePct'] : null,
            allowSpecialCard: isset($data['allowSpecialCard']) ? (bool) $data['allowSpecialCard'] : null,
            maxSpecialCards: isset($data['maxSpecialCards']) ? (int) $data['maxSpecialCards'] : null,
            maxPlayers: isset($data['maxPlayers']) ? (int) $data['maxPlayers'] : null,
            accessCode: $data['accessCode'] ?? null,
            // null = no cambiar la contraseña existente (el frontend nunca precarga el hash)
            roomPassword: $data['roomPassword'] ?? null,
            hostUserId: $data['hostUserId'] ?? null,
        );
    }
}
