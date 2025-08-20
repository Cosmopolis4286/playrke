import axios from 'axios';
import type { Method } from 'axios'; // Importa el tipo Method de axios original

// Obtener token CSRF desde meta tag
const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
} else {
    console.warn(
        '⚠️ CSRF token no encontrado. Asegúrate de incluir en tu layout:\n' +
        '<meta name="csrf-token" content="{{ csrf_token() }}">'
    );
}

// Cabeceras y configuración global adicional
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.timeout = 10000; // 10 segundos

// Exporta axios y el tipo Method
export { axios, type Method };
export default axios;
