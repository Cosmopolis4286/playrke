/**
 * Controla la visibilidad del loader global.
 *
 * La clase `is-loading` ya viene puesta en <html> desde el HTML inicial
 * (auth-layout.blade.php) — sin depender de JS para activarla, así nunca
 * hay parpadeo ni condición de carrera con el orden de carga de módulos.
 *
 * Este módulo solo se encarga de QUITARLA, y debe hacerse cuando la SPA
 * (Vue/Inertia) realmente terminó de montar el contenido — no cuando el
 * navegador dispara `load` (que espera TODOS los recursos: imágenes,
 * fuentes, websockets, etc.). Si una imagen de fondo tarda, `load` se
 * retrasa y el loader —opaco, cubre toda la pantalla— sigue tapando
 * contenido ya montado debajo, dejando una falsa sensación de "pantalla
 * en blanco/vacía".
 *
 * Por eso `hideAppLoader()` se exporta y se invoca explícitamente desde
 * inertia.ts justo después de `app.mount(el)`, en vez de depender de un
 * evento genérico del navegador.
 */

export function hideAppLoader(): void {
    const loader = document.getElementById('app-loader');
    if (!loader) return;

    loader.classList.add('is-hidden');
    loader.setAttribute('aria-busy', 'false');
    document.documentElement.classList.remove('is-loading');

    // Eliminar del DOM luego de la transición (debe coincidir con CSS transition: 0.5s)
    setTimeout(() => {
        loader.remove();
    }, 520);
}
