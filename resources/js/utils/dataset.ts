// resources/js/utils/datasetUtils.ts
import { getAppDataProperty } from '@/data/index';

/**
 * Extrae las keys de un tipo de mapa de datasets.
 * @example
 *   datasetKeys<DatasetMap>() // => ["coins", "countries", ...]
 */
export function datasetKeys<T extends Record<string, any[]>>() {
    return Object.keys({} as T) as (keyof T)[];
}

/**
 * Genera getters seguros para cualquier dataset dinámico en appData.
 * @param keys - Array de claves que se esperan en appData
 * @returns Un objeto con funciones que devuelven arrays tipados
 */
export function createDatasetGetters<T extends Record<string, any[]>>(keys: (keyof T)[]) {
    return keys.reduce((acc, key) => {
        acc[key] = () => {
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
