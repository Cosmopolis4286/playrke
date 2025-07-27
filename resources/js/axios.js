import axios from 'axios';

// Buscar el token CSRF en el meta tag del head
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token?.content) {
    // Setear token CSRF en las cabeceras comunes de axios
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('⚠️ CSRF token no encontrado en meta. Añade <meta name="csrf-token" content="{{ csrf_token() }}"> en tu layout.');
}

// Exponer axios globalmente para usar en consola o scripts sin importarlo
window.axios = axios;

export default axios;
