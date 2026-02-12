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
        public readonly string $date,
        public readonly string $time,
        public readonly string $roomType,
        public readonly string|int $gameMode,
        public readonly string|int $prizePoolType,
        public readonly array $allowedCurrencies,
        public readonly ?float $amountAddedPrizePool,
        public readonly float $roomCommn,
        public readonly ?float $jackpotPct,
        public readonly int $timeChip,
        public readonly int $rounds,
        public readonly ?float $roundsPct,
        public readonly float $serialsPct,
        public readonly int $numSerials,
        public readonly float $ctnPrice,
        public readonly ?array $roundsConfig,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'],
            time: $data['time'],
            roomType: $data['roomType'] ?? 'PV',
            gameMode: $data['gameMode'],
            prizePoolType: $data['prizePoolType'],
            allowedCurrencies: array_values((array) ($data['allowedCurrencies'] ?? [])),
            amountAddedPrizePool: isset($data['amountAddedPrizePool']) ? (float) $data['amountAddedPrizePool'] : null,
            roomCommn: (float) ($data['roomCommn'] ?? 20),
            jackpotPct: isset($data['jackpotPct']) ? (float) $data['jackpotPct'] : null,
            timeChip: (int) ($data['timeChip'] ?? 5),
            rounds: (int) ($data['rounds'] ?? 1),
            roundsPct: isset($data['roundsPct']) ? (float) $data['roundsPct'] : null,
            serialsPct: (float) ($data['serialsPct'] ?? 5),
            numSerials: (int) ($data['numSerials'] ?? 5),
            ctnPrice: (float) ($data['ctnPrice'] ?? 1),
            roundsConfig: $data['roundsConfig'] ?? null,
        );
    }
}
