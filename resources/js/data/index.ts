// resources/js/data/index.ts
import type { AppData, Coin, Country, Game, TimeZone } from '../types/dataModels.js';

let coins: Coin[] = [];
let countries: Country[] = [];
let games: Game[] = [];
let moduleFile = '';
let timeZones: TimeZone[] = [];
let appData: AppData;

export const getDynamic = <T = any>(key: string, defaultValue?: T): T | undefined => {
    return appData[key] ?? defaultValue;
};

export function initAppData(): void {
    document.addEventListener('DOMContentLoaded', () => {
        if (!(window as any).appData) {
            throw new Error('❌ window.appData no está definido. Asegúrate de declararlo en Blade antes de cargar los scripts.');
        }

        appData = (window as any).appData as AppData;

        coins = appData.coins ?? [];
        countries = appData.countries ?? [];
        games = appData.games ?? [];
        moduleFile = appData.moduleFile ?? '';
        timeZones = appData.timeZones ?? [];
    });
}

// Exportar también las variables para que otros módulos puedan importarlas
export { coins, countries, games, moduleFile, timeZones, appData };
