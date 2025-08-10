// Importación de configuración Axios para peticiones HTTP
import './axios';

// // Importamos funciones para manejar rutas Ziggy
// import { preloadZiggyRoutes, route } from './ziggy-wrapper.js';

// Importamos el archivo que inicializa otras funcionalidades de la app
import './show-app';

// window.route = route;
// window.ziggyReady = false;

// // Guardamos la promesa globalmente para que otros módulos puedan esperarla
// window.ziggyPreloadPromise = preloadZiggyRoutes();

// window.ziggyPreloadPromise.then(() => {
//     window.ziggyReady = true;
//     window.dispatchEvent(new Event('ziggyReady'));
// });

// // Exportamos estas funciones para que otros módulos puedan usarlas
// export { route };
