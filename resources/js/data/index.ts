// resources/js/data/index.ts
import type { AppData, Coin, Country, Game, TimeZone } from '../types/dataModels.js';

let _appData: AppData = {} as AppData;

/**
 * Inicializa appData dinámico desde window.appData.
 * Espera al DOM si aún no está cargado.
 */
export async function initAppData(): Promise<void> {
    if (document.readyState === 'loading') {
        await new Promise<void>((resolve) => {
            document.addEventListener('DOMContentLoaded', () => resolve(), { once: true });
        });
    }

    _appData = (window as any).appData ?? {} as AppData;
}

/**
 * Devuelve appData seguro
 */
export const getAppData = (): AppData => _appData;

/**
 * Getters seguros para cada propiedad dinámica
 */
export const getCoins = (): Coin[] => getAppData().coins ?? [];
export const getCountries = (): Country[] => getAppData().countries ?? [];
export const getGames = (): Game[] => getAppData().games ?? [];
export const getTimeZones = (): TimeZone[] => getAppData().timeZones ?? [];
export const getModuleFile = (): string => getAppData().moduleFile ?? '';
