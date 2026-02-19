export interface BingoConfigPageProps {
    imageBingoRules?: string | null
    bingoRules?: string | null
}

/**
 * Datos del formulario de configuración de reglas del bingo.
 * Usado en BingoConfigForm y useBingoConfig.
 */
export interface BingoRulesFormData {
    rules: string
    image?: File | null
}
