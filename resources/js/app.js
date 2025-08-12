// Importa la configuración personalizada de Axios para manejar peticiones HTTP
import './axios';

// Importa la función showToast para mostrar notificaciones tipo toast
import { showToast } from '@/helpers/toastNotify.ts';

// Importa el script principal de la aplicación (inicialización, listeners, etc.)
import './show-app';

// Verifica si Laravel pasó errores al frontend mediante la variable global
// Si existen errores, los muestra uno por uno como notificaciones de error
if (window.LaravelErrors && window.LaravelErrors.length > 0) {
    window.LaravelErrors.forEach((msg) => {
        showToast(msg, 'error');
    });
}
