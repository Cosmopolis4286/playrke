// resources/js/helpers/modals.ts
interface ToggleModalOptions {
    id: string;
    state?: boolean;
}

/**
 * Abre o cierra un modal Alpine escuchando el evento global 'toggle-modal'
 */
export function toggleModal({ id, state }: ToggleModalOptions) {
    window.dispatchEvent(
        new CustomEvent('toggle-modal', { detail: { id, state } })
    );
}
