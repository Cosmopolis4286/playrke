/**
 * Información de zona horaria
 */
export interface TimeZone {
  readonly id: number // ID único de la zona horaria
  readonly name: string // Nombre descriptivo, ej: "America/Caracas"
  readonly utc: string // Offset UTC, ej: "UTC-04"
  readonly country_id?: number // ID del país asociado (opcional)
}
