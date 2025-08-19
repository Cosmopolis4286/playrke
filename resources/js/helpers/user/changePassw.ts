// resources/js/helpers/user/changePassw.ts
import { submitForm } from "../form/formCrudCore";

/**
 * Inicializa el módulo de cambio de contraseña:
 * - Abre modal
 * - Confirma cambio de contraseña
 */
export function initChangePassw(): void {
    document.addEventListener('click', (event) => {
        const target = event.target as HTMLElement;

        // Abrir modal de cambio de contraseña
        if (target?.id === 'change-password') {
            const modal = document.getElementById('modal-change-password');
            if (modal) $(modal).modal('show');
            return;
        }

        // Confirmar cambio de contraseña
        if (target?.matches("#btn-confirm-new-pwd[data-type='change_pwd']")) {
            submitForm('change-password', ['current_password', 'new_password']);
            return;
        }
    });
}