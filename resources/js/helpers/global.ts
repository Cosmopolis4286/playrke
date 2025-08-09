import $ from 'jquery';
import 'datatables.net-bs4';
import { SweetAlertIcon } from 'sweetalert2';
import { showToast } from './toastNotify.js';
import { submitForm } from './formCrud.js';

export function showSessionToast(message: string, icon: SweetAlertIcon): void {
    if (message) {
        showToast(`<div style="font-size: 15px;">${message}</div>`, icon);
    }
}

export function loadDataTable(languageUrl: string): void {
    const $table = $('table.dataTable');
    if ($table.length === 0 || typeof ($ as any).fn?.DataTable !== 'function') return;

    $table.DataTable({
        ordering: true,
        order: [],
        language: { url: languageUrl },
        columnDefs: [{ targets: '_all', className: 'text-left' }],
    });
}

export function setupGlobalEvents(): void {
    // Delegación de eventos para clicks relevantes
    document.addEventListener('click', (event) => {
        const target = event.target as HTMLElement;

        if (target?.id === 'change-password') {
            $('#modal-change-password').modal('show');
            return;
        }

        if (target?.matches("#btn-confirm-new-pwd[data-type='change_pwd']")) {
            submitForm('change-password', ['current_password', 'new_password']);
            return;
        }
    });

    // Evitar dragstart en imágenes para evitar arrastrar accidentalmente
    document.addEventListener('dragstart', (event) => {
        if ((event.target as HTMLElement)?.tagName === 'IMG') {
            event.preventDefault();
        }
    });

    // Logout con fetch y CSRF token dinámicos
    document.querySelectorAll<HTMLButtonElement>('.logout-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const logoutUrl = (window as any).logoutUrl ?? '/logout';
            const loginRedirectUrl = (window as any).loginRedirectUrl ?? '/login';
            const csrfToken = (window as any).csrfToken ?? '';

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
}
