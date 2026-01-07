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

export type CrudAction = 'create' | 'edit' | 'delete';
export type ModuleAction = CrudAction | string;

/**
 * Definición de un módulo CRUD dinámico
 */
export interface ModuleType<
    TRoutes extends Readonly<Record<string, string>> = Readonly<Record<string, string>>
> {
    /** ID único del módulo */
    moduleId: string;

    /** Sufijo del título del formulario */
    formTitleSuffix: string;

    /** Rutas del módulo (tipadas por const assertion) */
    routes: TRoutes;

    /** Acciones adicionales (no CRUD base) */
    otherCasesMap?: Partial<Record<keyof TRoutes | string, OtherCase>>;

    /** Campos requeridos para submit automático */
    requiredFields?: readonly string[];

    /** Hook para inyectar data en el formulario */
    insertInfo?: (action: ModuleAction, data?: unknown) => void;

    /** Hook para inicializar listeners personalizados */
    initListeners?: () => void;
}

