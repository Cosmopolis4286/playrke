// resources/js/types/window.d.ts
import { SweetAlertIcon } from 'sweetalert2';

declare global {
    interface Window {
        Session?: { message: string; type: SweetAlertIcon };
        LaravelErrors?: string[];
        loadDataTables?: boolean;
        _vueMounted?: boolean; // si usas vueAppIsReady
        vueAppIsReady?: () => void;
    }
}

// Esto es obligatorio para que sea un módulo
export { };
