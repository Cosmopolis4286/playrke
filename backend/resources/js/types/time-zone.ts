/**
 * Información de zona horaria
 */
export interface TimeZone {
  readonly id: string // Snowflake ID serializado como string
  readonly name: string // Nombre descriptivo, ej: "America/Caracas"
  readonly utc: string // Offset UTC, ej: "UTC-04"
  readonly country_id?: string // ID del país asociado (opcional)
}
