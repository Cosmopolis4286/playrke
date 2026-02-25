<?php

namespace App\DTOs\Games\Bingo\Room\Automatic;

/**
 * DTO para la creación de una Sala de Bingo Automática.
 * Basado en: 2024_06_15_07_create_bingo_rooms_automatic_table
 */
final class BingoRoomAutomaticCreateDTO
{
    public function __construct(
        public readonly string        $time,
        public readonly string        $roomType,
        public readonly string|int    $gameMode,
        public readonly string|int    $prizePoolType,
        public readonly array         $allowedCurrencies,
        public readonly float         $roomCommission,
        public readonly int           $rounds,
        public readonly string        $useSerials,       // 'Y' | 'N'
        public readonly ?string       $date              = null,
        public readonly ?array        $days              = null,
        public readonly ?float        $cardPriceUsd      = null,
        public readonly ?float        $usdExchangeRoundStep = null,
        public readonly ?array        $cardPriceByCurrency  = null,
        public readonly ?float        $amountAddedPrizePool = null,
        public readonly ?float        $jackpotPct        = null,
        public readonly ?string       $timeChip          = null,
        public readonly ?float        $roundsPct         = null,
        public readonly ?float        $serialsPct        = null,
        public readonly ?int          $numSerials        = null,
        public readonly ?array        $roundsConfig      = null,
        public readonly bool          $isDraft           = false,
        public readonly string        $inLive            = 'N',
    ) {}

    public static function fromArray(array $data): self
    {
        $allowedCurrencies   = array_map('strval', array_values((array) ($data['allowedCurrencies'] ?? [])));
        $cardPriceByCurrency = is_array($data['cardPriceByCurrency'] ?? null)
            ? array_map(fn($v) => (float) $v, $data['cardPriceByCurrency'])
            : [];

        $useSerials = filter_var($data['useSerials'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'Y' : 'N';

        return new self(
            time:                   $data['time'],
            roomType:               (string) ($data['roomType'] ?? 'PRIVATE'),
            gameMode:               $data['gameMode'],
            prizePoolType:          $data['prizePoolType'],
            allowedCurrencies:      $allowedCurrencies,
            roomCommission:         (float) ($data['roomCommn'] ?? 20),
            rounds:                 (int)   ($data['rounds']   ?? 1),
            useSerials:             $useSerials,
            date:                   $data['date'] ?? null,
            days:                   isset($data['days']) && is_array($data['days']) ? $data['days'] : null,
            cardPriceUsd:           isset($data['cardPriceUsd'])  ? (float) $data['cardPriceUsd']  : null,
            usdExchangeRoundStep:   isset($data['usdExchangeRoundStep']) ? (float) $data['usdExchangeRoundStep'] : null,
            cardPriceByCurrency:    $cardPriceByCurrency,
            amountAddedPrizePool:   isset($data['amountAddedPrizePool']) ? (float) $data['amountAddedPrizePool'] : null,
            jackpotPct:             isset($data['jackpotPct'])  ? (float) $data['jackpotPct']  : null,
            timeChip:               isset($data['timeChip'])   ? (string) $data['timeChip']   : null,
            roundsPct:              isset($data['roundsPct'])  ? (float)  $data['roundsPct']  : null,
            serialsPct:             isset($data['serialsPct']) ? (float)  $data['serialsPct'] : null,
            numSerials:             isset($data['numSerials']) ? (int)    $data['numSerials'] : null,
            roundsConfig:           $data['roundsConfig'] ?? null,
            isDraft:                (bool) ($data['isDraft'] ?? false),
            inLive:                 ($data['inLive'] ?? 'N') === 'Y' ? 'Y' : 'N',
        );
    }
}
