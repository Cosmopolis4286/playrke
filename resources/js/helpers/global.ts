import $ from 'jquery';
import 'datatables.net-bs4';
import { SweetAlertIcon } from 'sweetalert2';
import { showToast } from './toastNotify.js';
import { submitForm } from './formCrud.js';

/**
 * 📢 Muestra un toast con el mensaje de sesión
 * @param message Texto del mensaje
 * @param icon Tipo de ícono (SweetAlertIcon)
 */
export function showSessionToast(message: string, icon: SweetAlertIcon): void {
    if (message?.trim()) {
        showToast(`<div style="font-size: 15px;">${message}</div>`, icon);
    }
}

/**
 * 📊 Inicializa DataTables en tablas con clase .dataTable
 * @param languageUrl URL del archivo de idioma de DataTables
 */
export function loadDataTable(languageUrl: string): void {
    const $table = $('table.dataTable');
    if (!$table.length || typeof ($ as any).fn?.DataTable !== 'function') return;

    $table.DataTable({
        ordering: true,
        order: [],
        language: { url: languageUrl },
        columnDefs: [{ targets: '_all', className: 'text-left' }],
    });
}

/**
 * 🌐 Configura eventos globales de la aplicación
 * - Manejo de modales (cambio de contraseña)
 * - Prevención de arrastre de imágenes
 * - Logout con CSRF token
 * - Inicializa popovers
 */
export function setupGlobalEvents(): void {
    /**
     * 🖱 Delegación de eventos de click
     */
    document.addEventListener('click', (event) => {
        const target = event.target as HTMLElement;

        // Abrir modal de cambio de contraseña
        if (target?.id === 'change-password') {
            $('#modal-change-password').modal('show');
            return;
        }

        // Confirmar cambio de contraseña
        if (target?.matches("#btn-confirm-new-pwd[data-type='change_pwd']")) {
            submitForm('change-password', ['current_password', 'new_password']);
            return;
        }
    });

    /**
     * 🖼 Evitar arrastre accidental de imágenes
     */
    document.addEventListener('dragstart', (event) => {
        if ((event.target as HTMLElement)?.tagName === 'IMG') {
            event.preventDefault();
        }
    });

    /**
     * 🚪 Logout con CSRF token dinámico
     */
    document.querySelectorAll<HTMLButtonElement>('.logout-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const { logoutUrl = '/logout', loginRedirectUrl = '/login', csrfToken = '' } = window as any;

            fetch(logoutUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                },
            })
                .then((response) => {
                    if (response.ok) {
                        window.location.href = loginRedirectUrl;
                    } else {
                        alert('Error al cerrar sesión');
                    }
                })
                .catch((error) => console.error('Error en logout:', error));
        });
    });

    /**
     * 💬 Inicializar popovers si existen
     */
    if ($('.popover').length > 0 && typeof ($ as any).fn?.popover === 'function') {
        $('.popover').popover();
    }
}

/**
 * 🚀 Inicializa la aplicación cuando el DOM esté listo
 * - Configura eventos globales
 * - Muestra mensaje de sesión si existe
 * - Muestra errores de Laravel si existen
 * - Inicializa DataTables si está habilitado
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1️⃣ Inicializar eventos globales
    setupGlobalEvents();

    // 2️⃣ Mostrar mensaje de sesión
    if (window.Session?.message) {
        showSessionToast(window.Session.message, window.Session.type || 'success');
    }

    // 3️⃣ Mostrar errores de Laravel
    if (Array.isArray(window.LaravelErrors) && window.LaravelErrors.length > 0) {
        window.LaravelErrors.forEach((msg: string) => {
            showToast(msg, 'error');
        });
    }

    // 4️⃣ Inicializar DataTables si está habilitado
    if (window.loadDataTables) {
        const languageUrl = '/lang/datatables/es.json'; // ⚠️ Cambiar por tu ruta real de idioma
        loadDataTable(languageUrl);
    }
});

/**
 * 🛡 Tipos globales para TypeScript
 */
declare global {
    interface Window {
        Session?: { message: string; type: SweetAlertIcon };
        LaravelErrors?: string[];
        loadDataTables?: boolean;
    }
}
