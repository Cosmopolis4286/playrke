// resources/js/data/index.ts
import { getJsonData } from '@/utils/getJsonData';

let _appData: Record<string, unknown> = {};
let _initialized = false;
let _initPromise: Promise<void> | null = null;

/**
 * Inicializa appData dinámico desde <script id="page-data">.
 * Garantiza que solo se ejecute una vez.
 */
export async function initAppData(): Promise<void> {
    if (_initialized) return;
    if (_initPromise) return _initPromise;

    _initPromise = (async () => {
        if (document.readyState === 'loading') {
            await new Promise<void>((resolve) =>
                document.addEventListener('DOMContentLoaded', () => resolve(), { once: true })
            );
        }

        const data = getJsonData<Record<string, unknown>>('page-data') ?? {};

        if (typeof data !== 'object' || data === null) {
            console.warn('⚠️ appData no es un objeto válido. Se inicializa vacío.');
            _appData = {};
        } else {
            _appData = data;
        }

        _initialized = true;
    })();

    return _initPromise;
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
