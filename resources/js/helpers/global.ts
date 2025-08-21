import { showToast } from './toastNotify';
import { loadDataTable } from './dataTables';
import { initChangePassw } from './user/changePassw';
import { initLogout } from './user/logout';

/**
 * Muestra los mensajes de error provenientes de Laravel.
 */
function showLaravelErrors(errors: string[]): void {
    if (!Array.isArray(errors) || errors.length === 0) return;

    errors.forEach((msg) => {
        try {
            showToast(msg, 'error');
        } catch (err) {
            console.error('❌ Error mostrando toast de error:', err);
        }
    });

    console.log(`✅ ${errors.length} LaravelErrors mostrados`);
}

/**
 * Inicializa DataTables si hay tablas en el DOM.
 */
function initDataTables(): void {
    const tables = document.querySelectorAll('table.data-table');
    if (!window.loadDataTables || tables.length === 0) return;

    try {
        const languageUrl = '/lang/datatables/es.json';
        loadDataTable(languageUrl);
        console.log(`✅ DataTables inicializadas (${tables.length} tablas)`);
    } catch (err) {
        console.error('❌ Error inicializando DataTables:', err);
    }
}

/**
 * Inicializa la aplicación globalmente
 */
export function initGlobal(): void {
    try {
        // Bloquear arrastre de imágenes
        document.addEventListener('dragstart', (event) => {
            if ((event.target as HTMLElement)?.tagName === 'IMG') {
                event.preventDefault();
            }
        });

        // Logout
        initLogout();

        // Cambio de contraseña si se habilita
        initChangePassw();
        console.log('✅ initChangePassw ejecutado');

        // Mensaje de sesión
        if (window.Session?.message?.trim()) {
            showToast(window.Session.message, window.Session.type || 'success');
        }

        // Errores de Laravel
        showLaravelErrors(window.LaravelErrors);

        // Inicializar DataTables
        initDataTables();

        // Limpieza de foco en modales
    } catch (err) {
        console.error('❌ Error en initGlobal:', err);
    }
}

/**
 * Inicialización segura con valores por defecto
 */
export function initGlobalSafe(): void {
    window.loadDataTables ??= false;
    window.Session ??= { message: '', type: 'success' };
    window.LaravelErrors ??= [];
    initGlobal();
}
