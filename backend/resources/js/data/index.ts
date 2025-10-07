import { getJsonData } from '@/utils/getJsonData';

/**
 * Interfaz global de appData
 * ⚠️ Ajusta las claves según lo que realmente usas
 */
export interface AppData {
  moduleFile?: string;
  enableFormCrud?: boolean;
  [key: string]: unknown;
}

let _appData: AppData = {};
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

    const data = getJsonData<AppData>('page-data') ?? {};

    if (typeof data !== 'object' || data === null) {
      console.warn('⚠️ appData no es un objeto válido. Se inicializa vacío.');
      _appData = {};
    } else {
      _appData = Object.freeze({ ...data });
    }

    _initialized = true;
  })();

  return _initPromise;
}

/**
 * Devuelve appData completo (solo lectura).
 */
export const getAppData = (): Readonly<AppData> => _appData;

/**
 * Devuelve una propiedad tipada de appData.
 */
export function getAppDataProperty<T>(key: keyof AppData, defaultValue: T): T {
  const value = _appData[key];
  return (value === undefined || value === null) ? defaultValue : (value as T);
}
