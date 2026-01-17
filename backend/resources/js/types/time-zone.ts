/**
 * Información de zona horaria
 */
export interface TimeZone {
  readonly id: string // Ej: "America/Sao_Paulo"
  readonly name: string // Nombre descriptivo, ej: "Brasília Time"
  readonly utc: string // Offset UTC, ej: "UTC-03"
  readonly country_id?: number // ID del país asociado (opcional)
}
