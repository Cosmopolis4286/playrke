import { showToast } from './toastNotify';
import { loadDataTable } from './dataTables';
import { initChangePassw } from './user/changePassw';
import { initLogout } from './user/logout';
import { initModalFocus } from '@/helpers/modal/modals';

/**
 * Bloquear arrastre de imágenes
 * Previene que las imágenes sean arrastradas accidentalmente.
 */
async function initDragStart(): Promise<void> {
    document.addEventListener('dragstart', (event) => {
        if ((event.target as HTMLElement)?.tagName === 'IMG') {
            event.preventDefault();
        }
    });
}

/**
 * Mostrar mensajes de Laravel
 * @param errors - Array de errores generados por Laravel
 */
async function showLaravelMessages(errors: string[] = []): Promise<void> {
    if (!errors.length) return;

    for (const msg of errors) {
        try {
            showToast(msg, 'error');
        } catch (err) {
            console.error('❌ Error mostrando toast de error:', err);
        }
    }

    console.log(`✅ ${errors.length} LaravelErrors mostrados`);
}

/**
 * Inicializa DataTables si hay tablas en el DOM
 */
async function initDataTables(): Promise<void> {
    const tables = document.querySelectorAll('table.data-table');
    if (!tables.length) return;

    try {
        const languageUrl = '/lang/datatables/es.json';
        await loadDataTable(languageUrl);
        console.log(`✅ DataTables inicializadas (${tables.length} tablas)`);
    } catch (err) {
        console.error('❌ Error inicializando DataTables:', err);
    }
}

/**
 * Inicializa la aplicación globalmente
 */
export async function initGlobal(): Promise<void> {
    try {
        // 🔹 Logout primero por seguridad
        await initLogout();

        // 🔹 Bloquear arrastre de imágenes
        await initDragStart();

        // 🔹 Inicializar modales accesibles
        await initModalFocus();

        // 🔹 Cambio de contraseña
        await initChangePassw();
        console.log('✅ initChangePassw ejecutado');

        // 🔹 Mostrar mensajes de sesión Laravel
        if (window.Session?.message?.trim()) {
            showToast(window.Session.message, window.Session.type || 'success');
        }

        // 🔹 Mostrar errores Laravel
        await showLaravelMessages(window.LaravelErrors);

        // 🔹 Inicializar DataTables
        await initDataTables();

        console.log('✅ initGlobal completado');
    } catch (err) {
        console.error('❌ Error en initGlobal:', err);
    }
}

/**
 * Inicialización segura con valores por defecto
 */
export async function initGlobalSafe(): Promise<void> {
    console.log('initGlobalSafe');
    await initGlobal();
}
