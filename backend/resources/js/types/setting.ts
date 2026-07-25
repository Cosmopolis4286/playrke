/**
 * Parámetro de configuración del sistema
 */
export interface Setting {
    id: string                      // Snowflake ID
    param: string                   // Nombre del parámetro (snake_case, único)
    value: string | null            // Valor del parámetro
    status: 'ACTIVE' | 'INACTIVE'
    created_at?: string
    updated_at?: string
}

export interface SettingPageProps {
    settings: Setting[]
    roleUser: string
}
