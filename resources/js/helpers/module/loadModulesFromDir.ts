// resources/js/helpers/loadModulesFromDir.ts

/**
 * Carga y prueba todos los módulos de un directorio de manera dinámica.
 *
 * @param globPattern Patrón de archivos para importar (ej: '../modules/**/*.ts')
    * @param initFunc Nombre de la función a ejecutar en cada módulo(opcional)
        */
export async function loadModulesFromDir(globPattern: string, initFunc?: string) {
    try {
        // Escanear todos los módulos que coinciden con el patrón
        const modules = import.meta.glob(globPattern);
        const keys = Object.keys(modules);

        if (keys.length === 0) {
            console.warn(`⚠️ No se encontraron módulos con el patrón: ${globPattern}`);
            return;
        }

        console.log(`🔍 Se encontraron ${keys.length} módulos:`, keys);

        // Iterar e importar cada módulo dinámicamente
        for (const path of keys) {
            try {
                const module = await modules[path]();
                console.log(`✅ Módulo importado correctamente: ${path}`);

                if (initFunc && typeof module[initFunc] === 'function') {
                    try {
                        module[initFunc]();
                        console.log(`🟢 Función ${initFunc} ejecutada en ${path}`);
                    } catch (err) {
                        console.error(`❌ Error ejecutando función ${initFunc} en ${path}:`, err);
                    }
                }
            } catch (err) {
                console.error(`❌ Error importando módulo: ${path}`, err);
            }
        }
    } catch (err) {
        console.error('❌ Error en loadModulesFromDir:', err);
    }
}
