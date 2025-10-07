// js/global/global.ts

import { showToast } from './toastNotify';
import { loadDataTable } from './dataTables';
import { initChangePassw } from './user/changePassw';
import { initLogout } from './user/logout';
import { initModalFocus } from '@/helpers/modal/modals';
import { initTableActions } from '@/alpine/tableSelection';

/**
 * Inicializa el bloqueo de arrastre de imágenes
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
 * Muestra los mensajes de error generados por Laravel
 * @param errors Array de mensajes de error
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
 * Inicializa DataTables en todas las tablas con clase .data-table
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

        // 🔹 Mostrar mensajes flash de sesión
        if (window.Session?.message?.trim()) {
            showToast(window.Session.message, window.Session.type || 'success');
        }

        // 🔹 Mostrar errores de Laravel (validación)
        if (window.LaravelErrors?.length) {
            await showLaravelMessages(window.LaravelErrors);
        }

        // 🔹 Inicializar DataTables
        await initDataTables();

        // 🔹 Inicializar listeners globales de selección de tablas
        initTableActions();

        console.log('✅ initGlobal completado');
    } catch (err) {
        console.error('❌ Error en initGlobal:', err);
    }
}

/**
 * Inicialización segura con valores por defecto
 */
export async function initGlobalSafe(): Promise<void> {
    console.log('initGlobalSafe iniciado');
    await initGlobal();
}
