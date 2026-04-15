/**
 * Parámetro de configuración del sistema
 */
export interface Setting {
    readonly id: string                      // Snowflake ID
    readonly param: string                   // Nombre del parámetro (snake_case, único)
    readonly value: string | null            // Valor del parámetro
    readonly status: 'ACTIVE' | 'INACTIVE'
    readonly created_at?: string
    readonly updated_at?: string
}

export interface SettingPageProps {
    settings: Setting[]
    roleUser: string
}
