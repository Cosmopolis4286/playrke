// Función genérica para ejecutar código cuando el DOM esté listo
export function onDOMReady(callback: () => void) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        callback();
    }
}
