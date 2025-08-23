// resources/js/utils/getJsonData.ts
export function getJsonData<T>(id: string): T | null {
    const el = document.getElementById(id);
    if (!el) {
        console.warn(`Elemento con ID "${id}" no encontrado.`);
        return null;
    }

    try {
        return JSON.parse(el.textContent || '{}') as T;
    } catch (e) {
        console.error(`Error parseando datos de "${id}":`, e);
        return null;
    } finally {
        // 🔹 Usa Alpine para la eliminación, si existe
        if (window.Alpine && typeof window.Alpine.mutateDom === "function") {
            window.Alpine.mutateDom(() => el.remove());
        } else {
            // fallback normal si Alpine no está cargado
            el.remove();
        }
    }
}
