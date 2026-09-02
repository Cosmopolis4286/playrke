/**
 * Parámetro de configuración de la aplicación
 */
export interface AppParameter {
    id: string                      // Snowflake ID
    param: string                   // Nombre del parámetro (snake_case, único)
    value: string | null            // Valor del parámetro
    status: 'ACTIVE' | 'INACTIVE'
    created_at?: string
    updated_at?: string
}

export interface AppParameterPageProps {
    parameters: AppParameter[]
    roleUser: string
    /** Parámetros críticos del sistema (AppParameter::CRITICAL_PARAMS) — no se pueden eliminar. */
    critical_params: string[]
}
