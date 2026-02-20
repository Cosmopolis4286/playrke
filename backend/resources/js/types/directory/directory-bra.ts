/**
 * Directorio PIX (Brasil)
 */
export interface DirectoryBR {
    readonly id: string
    readonly name: string
    readonly bank_id: string
    readonly bank: { id: string; name: string }
    readonly pix_key_type: string
    readonly pix_key_value: string
    readonly formatted_key?: string
    readonly created_at?: string
    readonly updated_at?: string
}
