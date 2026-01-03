import type { SweetAlertIcon } from 'sweetalert2';

/**
 * Acción personalizada ejecutable desde el CRUD
 */
export interface OtherCase {
    routeName: string;                 // Nombre de la ruta backend
    text: string;                      // Texto del modal
    icon: SweetAlertIcon;              // Icono SweetAlert
    confirmButtonText: string;         // Texto del botón confirmar
    method?: 'PUT' | 'DELETE';         // Método HTTP (default: PUT)
}

/**
 * Definición de un módulo CRUD dinámico
 */
export interface ModuleType {
    moduleId: string;
    formTitleSuffix: string;

    // Generador de rutas del módulo
    route?: (name: string, params?: Record<string, string | number>) => string;

    // Acciones adicionales (no create/update)
    otherCasesMap?: Record<string, OtherCase>;

    // Campos requeridos para submit automático
    requiredFields?: string[];

    // Hook para inyectar data en el formulario
    insertInfo?: (action: 'create' | 'edit' | string, data?: unknown) => void;

    // Hook para listeners personalizados
    initListeners?: () => void;
}
