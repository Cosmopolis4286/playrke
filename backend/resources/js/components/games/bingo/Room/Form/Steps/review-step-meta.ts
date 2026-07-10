export type ReviewStepKey = 'basic' | 'currencies' | 'rounds'

export const STEP_LABELS: Record<ReviewStepKey, string> = {
    basic:      'Datos básicos',
    currencies: 'Monedas y precios',
    rounds:     'Rondas',
}

export const STEP_ERROR_KEYS: Record<ReviewStepKey, string[]> = {
    basic:      ['date', 'time', 'days', 'roomType', 'gameMode', 'prizePoolType', 'roomCommn', 'timeChip', 'rounds', 'jackpotPct', 'amountAddedPrizePool'],
    currencies: ['allowedCurrencies', 'cardPriceUsd', 'cardPriceByCurrency', 'usdExchangeRoundStep'],
    rounds:     ['roundsPct', 'roundsConfig', 'serialsPct', 'numSerials', 'customCardPricePct', 'maxSpecialCards', 'percentageSum'],
}
