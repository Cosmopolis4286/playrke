// resources/js/types/appTypes.ts

export interface AppData {
    countriesCommerce: any[];
    timeZones: string[];
    coinsCommerce: any[];
}

export interface SessionData {
    message: boolean;
    passw: string | null;
    error: string | null;
}
