/**
 * Información de un país
 */
export interface Country {
    readonly id: number
    readonly name: string
    readonly iso_code_2: string // Código ISO, ej: "BR"
    readonly iso_code_3: string // Código ISO, ej: "BRA"
}
