<?php

namespace App\DTOs\Games\Bingo\Room\Automatic;

/**
 * DTO para la actualización de una Sala de Bingo Automática.
 * Basado en: 2024_06_15_07_create_bingo_rooms_automatic_table
 */
final class BingoRoomAutomaticUpdateDTO
{
    public function __construct(
        public readonly ?string       $time                = null,
        public readonly ?string       $date                = null,
        public readonly ?array        $days                = null,
        public readonly ?string       $roomType            = null,
        public readonly string|int|null $gameMode          = null,
        public readonly string|int|null $prizePoolType     = null,
        public readonly ?array        $allowedCurrencies   = null,
        public readonly ?float        $roomCommission      = null,
        public readonly ?int          $rounds              = null,
        public readonly ?bool         $useSerials          = null,
        public readonly ?float        $cardPriceUsd        = null,
        public readonly ?float        $usdExchangeRoundStep = null,
        public readonly ?array        $cardPriceByCurrency  = null,
        public readonly ?float        $amountAddedPrizePool = null,
        public readonly ?float        $jackpotPct          = null,
        public readonly ?string       $timeChip            = null,
        public readonly ?float        $roundsPct           = null,
        public readonly ?float        $serialsPct          = null,
        public readonly ?int          $numSerials          = null,
        public readonly ?array        $roundsConfig        = null,
        public readonly bool          $isDraft             = false,
        public readonly string        $inLive              = 'N',
    ) {}

    public static function fromArray(array $data): self
    {
        $allowedCurrencies   = isset($data['allowedCurrencies'])
            ? array_map('strval', array_values((array) $data['allowedCurrencies']))
            : null;

        $cardPriceByCurrency = is_array($data['cardPriceByCurrency'] ?? null)
            ? array_map(fn($v) => (float) $v, $data['cardPriceByCurrency'])
            : [];

        $useSerials = isset($data['useSerials'])
            ? filter_var($data['useSerials'], FILTER_VALIDATE_BOOLEAN)
            : null;

        return new self(
            time:                  $data['time']     ?? null,
            date:                  $data['date']     ?? null,
            days:                  isset($data['days']) && is_array($data['days']) ? $data['days'] : null,
            roomType:              $data['roomType'] ?? null,
            gameMode:              $data['gameMode'] ?? null,
            prizePoolType:         $data['prizePoolType'] ?? null,
            allowedCurrencies:     $allowedCurrencies,
            roomCommission:        isset($data['roomCommn'])             ? (float)  $data['roomCommn']             : null,
            rounds:                isset($data['rounds'])                ? (int)    $data['rounds']                : null,
            useSerials:            $useSerials,
            cardPriceUsd:          isset($data['cardPriceUsd'])          ? (float)  $data['cardPriceUsd']          : null,
            usdExchangeRoundStep:  isset($data['usdExchangeRoundStep'])  ? (float)  $data['usdExchangeRoundStep']  : null,
            cardPriceByCurrency:   $cardPriceByCurrency,
            amountAddedPrizePool:  isset($data['amountAddedPrizePool'])  ? (float)  $data['amountAddedPrizePool']  : null,
            jackpotPct:            isset($data['jackpotPct'])            ? (float)  $data['jackpotPct']            : null,
            timeChip:              isset($data['timeChip'])              ? (string) $data['timeChip']              : null,
            roundsPct:             isset($data['roundsPct'])             ? (float)  $data['roundsPct']             : null,
            serialsPct:            isset($data['serialsPct'])            ? (float)  $data['serialsPct']            : null,
            numSerials:            isset($data['numSerials'])            ? (int)    $data['numSerials']            : null,
            roundsConfig:          $data['roundsConfig'] ?? null,
            isDraft:               (bool) ($data['isDraft'] ?? false),
            inLive:                ($data['inLive'] ?? 'N') === 'Y' ? 'Y' : 'N',
        );
    }
}
