// helpers/form/ui/formCrudUI.ts
import Swal from '@/helpers/toastNotify';
import { showToast } from '@/helpers/toastNotify';
import { resetFormAndContainers } from '../formHelpers';
import { validateRequiredFields, clearFormValidation } from '@/shared/utils/form/fieldValidation';

/**
 * Responsabilidades de UI pura para CRUD de formularios.
 * - Modales
 * - Títulos
 * - Alerts / Toasts
 * - Reset visual
 * Sin lógica de red ni reglas de negocio.
 */

export function showModal(modalId?: string): void {
    if (!modalId) return;
    const modal = document.querySelector(modalId) as any;
    if (modal && typeof (window as any).$ === 'function') {
        (window as any).$(modalId).modal('show');
    }
}

export function hideModal(modalId?: string): void {
    if (!modalId) return;
    if (typeof (window as any).$ === 'function') {
        (window as any).$(modalId).modal('hide');
    }
}

export function setModalTitle(moduleId: string, title: string): void {
    const label = document.querySelector(`#modal-${moduleId}-label`);
    if (label) label.textContent = title;
}

export function resetFormUI(form: HTMLFormElement): void {
    if (!form.id) return;

    resetFormAndContainers(form.id);

    form.querySelectorAll<HTMLInputElement>('input[readonly]').forEach(input => {
        input.readOnly = false;
    });
}

export function validateAndFocus(formId: string, fields: string[]): boolean {
    const result = validateRequiredFields(formId, fields, true);

    if (!result.isValid) {
        if (result.firstInvalidField) {
            result.firstInvalidField.focus();
            result.firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        showToast(
            `Campos requeridos faltantes: ${result.missingFields.join(', ')}`,
            'warning'
        );
        return false;
    }

    clearFormValidation(formId);
    return true;
}

export async function confirmAction(
    text: string,
    icon: 'question' | 'warning' | 'success' | 'info' = 'question',
    confirmButtonText = 'Confirmar'
): Promise<boolean> {
    const result = await Swal.fire({
        text,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancelar'
    });

    return Boolean(result.isConfirmed);
}

export async function showGeneratedPassword(password: string, title = 'Nueva Contraseña'): Promise<void> {
    await Swal.fire({
        title,
        html: `
            <div style="text-align:left">
                <p><strong>Contraseña:</strong></p>
                <div style="display:flex;gap:8px;align-items:center">
                    <div id="password-display" style="flex:1;background:#f8f9fa;padding:10px;border-radius:5px;font-family:monospace;word-break:break-all">
                        ${password}
                    </div>
                    <button id="copy-password-btn" type="button" style="padding:8px 12px;background:#6366f1;color:white;border:none;border-radius:5px;cursor:pointer">
                        Copiar
                    </button>
                </div>
                <p style="margin-top:12px;font-size:13px;color:#6c757d">Guarda esta contraseña en un lugar seguro.</p>
            </div>
        `,
        icon: 'success',
        confirmButtonText: 'Entendido',
        allowOutsideClick: false,
        didOpen: () => {
            const btn = document.getElementById('copy-password-btn');
            if (!btn) return;
            btn.addEventListener('click', async () => {
                await navigator.clipboard.writeText(password);
                btn.textContent = 'Copiado';
            });
        }
    });
}

export function showSuccess(message = 'Operación exitosa'): void {
    showToast(message, 'success');
}

export function showError(message = 'Error al procesar la acción'): void {
    showToast(message, 'error');
}
