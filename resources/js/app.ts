// ==========================
// Importar Alpine.js y arrancarlo
// ==========================
import Alpine from 'alpinejs';

// Hacemos que Alpine sea accesible globalmente desde window
(window as any).Alpine = Alpine;

// Iniciamos Alpine para que todos los atributos x-data, x-show, x-on, etc., funcionen
Alpine.start();

// ==========================
// Loader o inicialización de la UI
// ==========================
import './mounApp.ts';
// Aquí puedes inicializar loaders, animaciones de entrada, estados iniciales de la app, etc.

// ==========================
// Módulos y funciones globales
// ==========================
import './helpers/global.ts';
// Funciones y helpers que deben estar siempre disponibles en toda la app
// Por ejemplo: listeners globales, utilidades, interceptores de eventos, etc.
