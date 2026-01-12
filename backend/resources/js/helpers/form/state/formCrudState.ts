// helpers/form/formCrudState.ts
import type { ModuleType } from '@js/helpers/module/types/formModule.types';

/**
 * Estado CRUD global (lazy, seguro y desacoplado).
 *
 * Este archivo es la ÚNICA fuente de verdad del estado del módulo CRUD
 * cargado dinámicamente.
 *
 * ❗ Nunca importar el loader aquí
 * ❗ Nunca mutar estado fuera de setFormCrudState
 */
export const formCrudState = {
    /** ID del módulo (form id base) */
    get moduleId(): string {
        return state.moduleId;
    },

    /** Archivo del módulo cargado dinámicamente */
    get moduleFile(): string {
        return state.moduleFile;
    },

    /** ID de la tabla asociada */
    get tableId(): string {
        return state.tableId;
    },

    /** Modal principal del CRUD */
    get modalId(): HTMLDivElement | null {
        return state.modalId;
    },

    /** Sufijo legible para títulos (ej: Usuario, Comercio) */
    get formTitleSuffix(): string {
        return state.formTitleSuffix;
    },

    /** Hook para inicializar formulario (create / edit) */
    get insertInfo(): ModuleType['insertInfo'] {
        return state.insertInfo;
    },

    /** Acciones custom del módulo */
    get otherCasesMap(): NonNullable<ModuleType['otherCasesMap']> {
        return state.otherCasesMap;
    },

    /** Campos requeridos del formulario */
    get requiredFields(): NonNullable<ModuleType['requiredFields']> {
        return state.requiredFields;
    },

    /** Rutas del módulo */
    get routes(): NonNullable<ModuleType['routes']> {
        return state.routes;
    },
};

/**
 * Estado interno REAL.
 * No exportar, no mutar directamente.
 */
const state: {
    moduleId: string;
    moduleFile: string;
    tableId: string;
    modalId: HTMLDivElement | null;
    formTitleSuffix: string;
    insertInfo: ModuleType['insertInfo'];
    otherCasesMap: NonNullable<ModuleType['otherCasesMap']>;
    requiredFields: NonNullable<ModuleType['requiredFields']>;
    routes: NonNullable<ModuleType['routes']>;
} = {
    moduleId: '',
    moduleFile: '',
    tableId: '',
    modalId: null,
    formTitleSuffix: '',
    insertInfo: () => { },
    otherCasesMap: {},
    requiredFields: [],
    routes: {},
};

/**
 * Inicializa o actualiza el estado CRUD desde el loader.
 *
 * ✔ Se ejecuta una sola vez por módulo
 * ✔ Admite inicialización parcial
 * ✔ Mantiene referencias estables
 */
export function setFormCrudState(payload: Partial<typeof state>): void {
    if (!payload || typeof payload !== 'object') {
        if (import.meta.env.DEV) {
            console.warn('⚠️ setFormCrudState llamado sin payload válido');
        }
        return;
    }

    Object.assign(state, payload);

    if (import.meta.env.DEV) {
        console.debug('🧩 formCrudState actualizado:', {
            moduleId: state.moduleId,
            tableId: state.tableId,
        });
    }
}
