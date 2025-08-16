// resources/ts/helpers/userInfoModule.ts

/**
 * Módulo para manejar la apertura de modales con información de usuario usando Axios.
 * @module userInfoModule
 */

import axios from "@/axios";

/**
 * Inicializa los eventos para mostrar la información de un usuario en un modal.
 * 
 * - Selecciona todos los elementos con la clase `.info-user-rke`.
 * - Al hacer click, realiza una petición GET con Axios para obtener la información.
 * - Inserta la respuesta en el modal `#app-info` y lo muestra.
 * 
 * Requiere:
 * - Un input hidden con id `#5K3_70K` que contenga el token CSRF.
 * - Un modal con id `#app-info` y un elemento `.modal-body` donde se cargará la información.
 */
export function initUserInfoModal(): void {
    const userElements = document.querySelectorAll<HTMLElement>(".info-user-rke");
    const modal = document.getElementById("app-info");
    const modalBody = modal?.querySelector<HTMLElement>(".modal-body");

    if (!modal || !modalBody) {
        console.error("Modal o modal-body no encontrado.");
        return;
    }

    userElements.forEach(element => {
        element.addEventListener("click", () => {
            const info = element.dataset.val;
            if (!info) {
                console.warn("No se encontró el valor 'data-val' en el elemento.");
                return;
            }

            const url = `{{ route('info-rke', ['page' => ':1']) }}`.replace(":1", info);

            axios.get(url)
                .then(response => {
                    modalBody.innerHTML = response.data;
                    // Mostrar modal usando Bootstrap 5 (sin jQuery)
                    const bootstrapModal = new (window as any).bootstrap.Modal(modal);
                    bootstrapModal.show();
                })
                .catch(error => {
                    console.error("No se pudo cargar el contenido.", error);
                });
        });
    });
}
