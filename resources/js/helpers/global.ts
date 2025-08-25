import { showToast } from './toastNotify';
import { loadDataTable } from './dataTables';
import { initChangePassw } from './user/changePassw';
import { initLogout } from './user/logout';

/**
 * Bloquear arrastre de imágenes
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
    if (!window.loadDataTables || !tables.length) return;

    try {
        const languageUrl = '/lang/datatables/es.json';
        await loadDataTable(languageUrl);
        console.log(`✅ DataTables inicializadas (${tables.length} tablas)`);
    } catch (err) {
        console.error('❌ Error inicializando DataTables:', err);
    }
}

/**
 * Inicializa modales Bootstrap 4 accesibles dinámicamente
 */
async function initBootstrapModals(): Promise<void> {
    await new Promise<void>((resolve) => {
        document.addEventListener('DOMContentLoaded', () => {
            const fallbackFocus = document.getElementById('main-content') || document.body;
            const modals = document.querySelectorAll<HTMLDivElement>('.modal');

            modals.forEach(modal => {
                // Inicialmente, marcar modal como inert si está oculto
                if (!modal.classList.contains('show')) modal.inert = true;

                // Mostrar modal
                $(modal).on('show.bs.modal', () => {
                    modal.inert = false;
                    modal.setAttribute('aria-hidden', 'false');
                });

                // Antes de cerrar
                $(modal).on('hide.bs.modal', () => {
                    if (modal.contains(document.activeElement)) fallbackFocus.focus();
                });

                // Después de cerrar
                $(modal).on('hidden.bs.modal', () => {
                    modal.inert = true;
                    modal.setAttribute('aria-hidden', 'true');
                });
            });

            console.log(`✅ ${modals.length} modales inicializados`);
            resolve();
        });
    });
}

/**
 * Inicializa la aplicación globalmente
 */
export async function initGlobal(): Promise<void> {
    try {
        // Logout primero
        await initLogout();

        // Bloquear arrastre de imágenes
        await initDragStart();

        // Inicializar modales accesibles
        await initBootstrapModals();

        // Cambio de contraseña
        await initChangePassw();
        console.log('✅ initChangePassw ejecutado');

        // Mostrar mensajes de sesión
        if (window.Session?.message?.trim()) {
            showToast(window.Session.message, window.Session.type || 'success');
        }

        // Mostrar errores Laravel
        await showLaravelMessages(window.LaravelErrors);

        // Inicializar DataTables
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
