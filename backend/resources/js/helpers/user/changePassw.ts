// resources/js/helpers/user/changePassw.ts
import { submitForm } from "../form/services/formSubmitService";

/**
 * Inicializa el módulo de cambio de contraseña:
 * - Abre modal
 * - Confirma cambio de contraseña
 */
export async function initChangePassw(): Promise<void> {
    document.addEventListener('click', (event) => {
        const target = event.target as HTMLElement;

        // Abrir modal de cambio de contraseña
        if (target?.id === 'resetPassword') {
            const modal = document.getElementById('modal-resetPassword');
            if (modal) $(modal).modal('show');
            return;
        }

        // Confirmar cambio de contraseña desde el modal
        if (target?.id === 'btn-confirm') {
            const modal = target.closest('#modal-resetPassword');
            if (modal) {
                submitForm('resetPassword-form', ['current_password', 'new_password', 'new_password_confirmation']);
                return;
            }
        }
    });
}
