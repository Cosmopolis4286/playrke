import axios from 'axios';

// Establecer el token CSRF para todas las peticiones axios
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token no encontrado en meta.');
}

window.axios = axios;
