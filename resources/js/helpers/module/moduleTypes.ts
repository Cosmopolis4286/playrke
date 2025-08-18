import { SweetAlertIcon } from 'sweetalert2';

/**
 * Tipo de cada acción personalizada que se puede ejecutar.
 */
export interface OtherCase {
    routeName: string;               // Nombre de la ruta backend
    text: string;                    // Texto para mostrar en el modal
    icon: SweetAlertIcon;            // Icono para SweetAlert
    confirmButtonText: string;       // Texto del botón de confirmación
    method?: 'PUT' | 'DELETE';       // Método HTTP (por defecto PUT)
}

// --------------------------------------
// Definición de la interfaz del módulo dinámico
// --------------------------------------
export interface ModuleType {
    formTitleSuffix: string;
    otherCasesMap?: Record<string, OtherCase>;
    requiredFields?: string[];
    viewId: string;
    initListeners?: () => void;
    insertInfo?: (action: string, data?: any) => void;
    route: (name: string, params?: Record<string, string | number>) => string;
}