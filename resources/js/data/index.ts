// resources/js/data/index.ts
import { getJsonData } from '@/utils/getJsonData';

let _appData: Record<string, unknown> = {};
let _initialized = false;

/**
 * Inicializa appData dinámico desde <script id="page-data">.
 * Garantiza que solo se ejecute una vez.
 */
export async function initAppData(): Promise<void> {
    if (_initialized) return;
    _initialized = true;

    if (document.readyState === 'loading') {
        await new Promise<void>((resolve) =>
            document.addEventListener('DOMContentLoaded', () => resolve(), { once: true })
        );
    }

    // Obtener datos del script JSON
    const data = getJsonData<Record<string, unknown>>('page-data') ?? {};

    if (typeof data !== 'object' || data === null) {
        console.warn('⚠️ appData no es un objeto válido. Se inicializa vacío.');
        _appData = {};
    } else {
        _appData = data;
    }

    // Eliminar el script del DOM para evitar accesos accidentales
    const scriptEl = document.getElementById('page-data');
    if (scriptEl) {
        scriptEl.remove();
    }
}

/**
 * Devuelve appData completo (solo lectura).
 */
export const getAppData = (): Readonly<Record<string, unknown>> => _appData;

/**
 * Devuelve una propiedad tipada de appData.
 * @param key - Nombre de la propiedad en appData
 * @param defaultValue - Valor por defecto si no existe
 */
export function getAppDataProperty<T>(key: string, defaultValue: T): T {
    return (key in _appData ? (_appData[key] as T) : defaultValue);
}
