// resources/js/types/dataModels.ts

export interface Coin {
    id: number;
    symbol: string;
    long_name: string;
    short_name: string;
    country: number;
}

export interface Country {
    id: number;
    name: string;
    iso_code: string;
}

export interface TimeZone {
    id: string;   // Ej: "America/Sao_Paulo"
    name: string; // Ej: "Brasília Time"
    offset: string; // Ej: "-03:00"
}
