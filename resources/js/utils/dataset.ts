// resources/js/utils/datasetUtils.ts
import { getAppDataProperty } from '@/data/index';
import { waitForAppData } from '@/app';

/**
 * Extrae las keys de un tipo de mapa de datasets.
 * @example
 *   datasetKeys<DatasetMap>() // => ["coins", "countries", ...]
 */
export function datasetKeys<T extends Record<string, any[]>>() {
    return Object.keys({} as T) as (keyof T)[];
}

/**
 * Genera getters seguros para datasets dinámicos en appData o en un DatasetMap.
 * 
 * @param datasetMap - Un mapa de datasets tipado opcional.
 *   Si no se pasa, se infiere automáticamente de `appData`.
 * 
 * @returns Un objeto con funciones que devuelven arrays tipados.
 */
export function createDatasetGetters<T extends Record<string, any[]>>(datasetMap?: T) {
    // Si no se pasa un datasetMap → se arma desde el appData
    const keys = datasetMap
        ? (Object.keys(datasetMap) as (keyof T)[])
        : (Object.keys(waitForAppData()) as (keyof T)[]);

    return keys.reduce((acc, key) => {
        acc[key] = () => {
            // Primero intentamos desde appData
            const data = getAppDataProperty<unknown>(key as string, []);

            if (!Array.isArray(data)) {
                console.warn(`⚠️ La propiedad "${String(key)}" en appData no es un array. Se devuelve array vacío.`);
                return [] as T[typeof key];
            }
            return data as T[typeof key];
        };
        return acc;
    }, {} as { [K in keyof T]: () => T[K] });
}
