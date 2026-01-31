/**
 * Interface para los bancos venezolanos
 */
export interface BanksVEN {
    readonly id: number | string
    readonly name: string
    readonly code?: string
}
