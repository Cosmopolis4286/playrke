import axios from 'axios';

// Obtener token CSRF desde meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (csrfToken) {
    // Configurar cabeceras por defecto
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
} else {
    console.warn(
        '⚠️ CSRF token no encontrado. Asegúrate de incluir: ' +
        '<meta name="csrf-token" content="{{ csrf_token() }}"> en tu layout.'
    );
}

// Configuración adicional de axios (opcional)
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.timeout = 10000; // Evita que llamadas cuelguen indefinidamente

export default axios;
