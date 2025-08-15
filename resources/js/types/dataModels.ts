// resources/js/types/dataModels.ts

/**
 * 💰 Información de una moneda
 */
export interface Coin {
    id: number;
    symbol: string;
    long_name: string;
    short_name: string;
    country: number; // ID del país asociado
}

/**
 * 🌍 Información de un país
 */
export interface Country {
    id: number;
    name: string;
    iso_code: string; // Código ISO, ej: "BR"
}

/**
 * ⏰ Información de zona horaria
 */
export interface TimeZone {
    id: string;      // Ej: "America/Sao_Paulo"
    name: string;    // Nombre descriptivo, ej: "Brasília Time"
    offset: string;  // Desfase respecto a UTC, ej: "-03:00"
}

/**
 * 📦 Información global de la app
 */
export interface AppData {
    coins: Coin[];
    countries: Country[];
    moduleFile: string;
    timeZones: TimeZone[];
}
