// resources/js/data/index.ts
import { getJsonData } from '@/utils/getJsonData';

let _appData: Record<string, any> = {};

/**
 * Inicializa appData dinámico desde <script id="page-data">.
 * Espera al DOM si aún no está listo.
 */
export async function initAppData(): Promise<void> {
    if (document.readyState === 'loading') {
        await new Promise<void>((resolve) => {
            document.addEventListener('DOMContentLoaded', () => resolve(), { once: true });
        });
    }

    // 🔹 Obtener datos del script JSON y eliminarlo del DOM por seguridad
    const data = getJsonData<Record<string, any>>('page-data');
    _appData = data ?? {};

    // Verificación opcional
    if (document.getElementById('page-data')) {
        console.warn('El script page-data sigue en el DOM (probablemente duplicado)');
    }

    if (!_appData || typeof _appData !== 'object') {
        console.warn('⚠️ appData inicializado pero no es un objeto válido. Se usará objeto vacío.');
        _appData = {};
    }
}

/**
 * Devuelve appData completo
 * @returns Record<string, any>
 */
export const getAppData = (): Record<string, any> => _appData;

/**
 * Getter genérico para cualquier propiedad dinámica de appData
 * @template T
 * @param key - Nombre de la propiedad en appData
 * @param defaultValue - Valor por defecto si no existe
 * @returns Valor tipado de la propiedad o defaultValue
 */
export function getAppDataProperty<T = any>(key: string, defaultValue: T = null): T {
    if (!_appData || typeof _appData !== 'object') return defaultValue;
    return (_appData[key] ?? defaultValue) as T;
}
