// utils/modalFocus.ts
export class ModalFocusManager {
    private lastTrigger: HTMLElement | null = null;

    constructor() {
        this.bindEvents();
    }

    private bindEvents() {
        // Guardar el trigger cuando un modal se abre
        document.addEventListener("click", (e) => {
            const target = e.target as HTMLElement;
            if (target && target.hasAttribute("data-bs-toggle") && target.getAttribute("data-bs-toggle") === "modal") {
                this.lastTrigger = target;
            }
        });

        // Cuando un modal se cierra, devolver foco al trigger
        document.addEventListener("hidden.bs.modal", () => {
            if (this.lastTrigger) {
                this.lastTrigger.focus();
                this.lastTrigger = null;
            }
        });
    }
}

// Inicializar el manager (llámalo una sola vez en tu app)
export const modalFocusManager = new ModalFocusManager();
