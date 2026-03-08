/**
 * Información de un país
 */
export interface Country {
    /** Snowflake ID serializado como string para evitar truncado en JS (> MAX_SAFE_INTEGER). */
    readonly id: string
    readonly name: string
    readonly iso_code_2: string // Código ISO, ej: "BR"
    readonly iso_code_3: string // Código ISO, ej: "BRA"
}
