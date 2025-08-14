import { Coin, Country, TimeZone } from '../types/dataModels.js';

interface AppData {
    coins: Coin[];
    countries: Country[];
    timeZones: TimeZone[];
}

// ⚠️ Aseguramos que appData exista
if (!(window as any).appData) {
    throw new Error('window.appData no está definido. Asegúrate de declarar la variable global en Blade.');
}

const appData = (window as any).appData as AppData;

// Exportar arrays tipados
export const coins: Coin[] = appData.coins;
export const arrCountries: Country[] = appData.countries;
export const arrTimeZones: TimeZone[] = appData.timeZones;
