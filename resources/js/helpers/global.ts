// resources/js/helpers/global.ts
import { SweetAlertIcon } from 'sweetalert2';
import { showToast } from './toastNotify';
import { loadDataTable } from './dataTables';
import { initChangePassw } from './user/changePassw';
import { initLogout } from './user/logout';

// ==========================
// Funciones
// ==========================

/**
 * Muestra un toast con el mensaje de sesión si existe.
 */
export function showSessionToast(message: string, icon: SweetAlertIcon = 'success'): void {
    if (!message?.trim()) return;

    try {
        showToast(`<div style="font-size: 15px;">${message}</div>`, icon);
        console.log('✅ Toast de sesión mostrado');
    } catch (err) {
        console.error('❌ Error mostrando toast de sesión:', err);
    }
}

/**
 * Configura eventos globales de la aplicación
 */
export function setupGlobalEvents(): void {
    try {
        document.addEventListener('dragstart', (event) => {
            if ((event.target as HTMLElement)?.tagName === 'IMG') {
                event.preventDefault();
            }
        });
        initLogout();
        console.log('✅ Eventos globales configurados');
    } catch (err) {
        console.error('❌ Error configurando eventos globales:', err);
    }
}

/**
 * Inicializa la aplicación globalmente
 */
export function initGlobal(): void {
    try {
        setupGlobalEvents();
        initChangePassw();
        console.log('✅ initChangePassw ejecutado');

        // Mensaje de sesión
        if (window.Session?.message) {
            showSessionToast(window.Session.message, window.Session.type || 'success');
        }

        // Errores de Laravel
        if (Array.isArray(window.LaravelErrors)) {
            window.LaravelErrors.forEach((msg) => {
                try {
                    showToast(msg, 'error');
                } catch (err) {
                    console.error('❌ Error mostrando toast de error:', err);
                }
            });
            if (window.LaravelErrors.length > 0) console.log('✅ LaravelErrors mostrados');
        }

        // Inicializar DataTables solo si hay tablas y está habilitado
        const tables = document.querySelectorAll('table.data-table');
        if (window.loadDataTables && tables.length > 0) {
            try {
                const languageUrl = '/lang/datatables/es.json';
                loadDataTable(languageUrl);
                console.log(`✅ DataTables inicializadas (${tables.length} tablas)`);
            } catch (err) {
                console.error('❌ Error inicializando DataTables:', err);
            }
        }
    } catch (err) {
        console.error('❌ Error en initGlobal:', err);
    }
}

/**
 * Inicialización segura con valores por defecto
 */
export function initGlobalSafe(): void {
    if (typeof window.loadDataTables === 'undefined') window.loadDataTables = false;
    if (!window.Session) window.Session = { message: '', type: 'success' };
    if (!Array.isArray(window.LaravelErrors)) window.LaravelErrors = [];
    initGlobal();
}