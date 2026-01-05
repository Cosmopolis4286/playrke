import { getAppDataProperty } from '@/data/index';
import { getAppData, type AppData } from '@/app';

/**
 * Extrae las keys de un tipo de mapa de datasets.
 * @example
 *   datasetKeys<DatasetMap>() // => ["currencies", "countries", ...]
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
    // Si no se pasa un datasetMap → se arma desde el appData ya inicializado
    const appData: AppData | T = datasetMap ?? getAppData();
    const keys = Object.keys(appData) as (keyof T)[];

    return keys.reduce((acc, key) => {
        acc[key] = () => {
            // Obtener dataset desde appData
            const data = getAppDataProperty<unknown>(key as string, []);
            if (!Array.isArray(data)) {
                console.warn(`⚠️ La propiedad "${String(key)}" en appData no es un array. Devuelvo [].`);
                return [] as T[typeof key];
            }
            return data as T[typeof key];
        };
        return acc;
    }, {} as { [K in keyof T]: () => T[K] });
}
