// resources/js/utils/getJsonData.ts

/**
 * Obtiene datos JSON de un <script type="application/json"> por ID
 * y luego lo elimina del DOM.
 * @param id - ID del elemento <script>
 * @returns Objeto tipado o null si no existe o hay error
 */
export function getJsonData<T extends object>(id: string): T | null {
    const el = document.getElementById(id);
    if (!el) {
        if (process.env.NODE_ENV !== 'production') {
            console.warn(`Elemento con ID "${id}" no encontrado.`);
        }
        return null;
    }

    const raw = el.textContent?.trim();
    if (!raw) return null;

    try {
        return JSON.parse(raw) as T;
    } catch (e) {
        console.error(`Error parseando datos de "${id}":`, e);
        return null;
    } finally {
        el.remove(); // 🔹 Limpieza centralizada aquí
    }
}
