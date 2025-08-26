import $ from 'jquery';

let lastFocusedButton: HTMLElement | null = null;

/**
 * Inicializa foco y accesibilidad en modales Bootstrap
 */
export function initModalFocus(): void {

    // 1️⃣ Delegación: botón que abre cualquier modal
    $(document).on('click', '.btn-open-modal', function () {
        lastFocusedButton = this as HTMLElement;
        console.log('Botón que abrió el modal:', lastFocusedButton);
    });

    // 2️⃣ Delegación: cualquier modal que se muestre
    $(document).on('shown.bs.modal', '.modal', function () {
        const modal = this as HTMLElement;

        // Primer input o elemento interactivo
        const focusable = modal.querySelector<HTMLElement>(
            'input, select, textarea, button, [href], [tabindex]:not([tabindex="-1"])'
        );
        focusable?.focus();

        modal.removeAttribute('aria-hidden');
        modal.removeAttribute('inert');

        console.log('Modal mostrado:', modal.id);
    });

    // 3️⃣ Delegación: devolver foco al cerrar
    $(document).on('hide.bs.modal', '.modal', function () {
        const modal = this as HTMLElement;

        if (lastFocusedButton) {
            lastFocusedButton.focus();
            lastFocusedButton = null;
        }

        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('inert', 'true');

        console.log('Modal cerrado:', modal.id);
    });
}
