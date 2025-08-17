// resources/js/data/index.ts
import { AppData, Coin, Country, Game, TimeZone } from '../types/dataModels.js';

// ⚠️ Verificamos que la variable global exista
if (!window.hasOwnProperty('appData')) {
    throw new Error('window.appData no está definido. Asegúrate de declarar la variable global en Blade.');
}

const appData = (window as any).appData as AppData;

/**
 * Valida un array y lanza un warning si está vacío o no existe
 * @param name Nombre del array (para el warning)
 * @param arr Array a validar
 * @returns Array seguro (vacío si no existía)
 */
function validateArray<T>(name: string, arr: T[] | undefined): T[] {
    if (!arr || arr.length === 0) {
        console.warn(`Advertencia: '${name}' está vacío o no definido.`);
        return [];
    }
    return arr;
}

/**
 * Valida un string y lanza un warning si está vacío
 * @param name Nombre del valor
 * @param value Valor a validar
 * @returns Valor seguro ('' si no existe)
 */
function validateString(name: string, value: string | undefined): string {
    if (!value) {
        console.warn(`Advertencia: '${name}' está vacío o no definido.`);
        return '';
    }
    return value;
}

// Exportamos arrays y valores tipados, validados
export const coins: Coin[] = validateArray('coins', appData.coins);
export const countries: Country[] = validateArray('countries', appData.countries);
export const games: Game[] = validateArray('countries', appData.games);
export const moduleFile: string = validateString('moduleFile', appData.moduleFile);
export const timeZones: TimeZone[] = validateArray('timeZones', appData.timeZones);
