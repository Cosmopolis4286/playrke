// resources/js/utils/getJsonData.ts

/**
 * Obtiene datos JSON de un <script type="application/json"> por ID y luego lo elimina del DOM.
 * @param id - ID del elemento <script>
 * @returns Objeto tipado o null si no existe o hay error
 */
export function getJsonData<T>(id: string): T | null {
    const el = document.getElementById(id);
    if (!el) {
        console.warn(`Elemento con ID "${id}" no encontrado.`);
        return null;
    }

    try {
        const data = JSON.parse(el.textContent || '{}') as T;
        el.remove(); // Eliminar el script para seguridad
        return data;
    } catch (e) {
        console.error(`Error parseando datos de "${id}":`, e);
        return null;
    }
}
