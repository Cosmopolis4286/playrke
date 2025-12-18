// resources/js/types/dataModels.ts

/**
 * Información de una moneda
 */
export interface Currency {
    id: number;
    symbol: string;
    description: string;
    name: string;
    country_id: number; // ID del país asociado
}

/**
 * Información de un país
 */
export interface Country {
    id: number;
    name: string;
    iso_code: string; // Código ISO, ej: "BR"
}
/**
 * Información básica de un juego
 */
export interface Game {
    readonly id: number;       // Identificador único del juego (ej: 1 para Bingo)
    readonly name: string;     // Nombre descriptivo del juego (ej: "Brasília Time")
    readonly status: number;   // Estado actual del juego (ej: 0=inactivo, 1=activo)
}

/**
 * Información de zona horaria
 */
export interface TimeZone {
    id: string;         // Ej: "America/Sao_Paulo"
    name: string;       // Nombre descriptivo, ej: "Brasília Time"
    utc: string;        // Offset UTC, ej: "UTC-03"
    country_id?: number; // ID del país asociado (opcional)
}
