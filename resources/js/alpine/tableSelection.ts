/**
 * @file tableSelection.ts
 * @description Componente Alpine.js para manejar la selección de filas en tablas.
 * Siempre mantiene un array reactivo de IDs seleccionados.
 * Permite toggle individual, toggle all y limpiar selección.
 * Se integra fácilmente con tableSelectionListener.ts
 */

import { onTableSelectionUpdate } from './tableSelectionListener';

/**
 * Componente Alpine para selección de filas en tablas.
 * @param allIds - Array inicial de IDs de la tabla
 * @param tableId - Identificador único de la tabla (requerido para listener)
 */
export function tableSelection(allIds: string[] = [], tableId: string = 'default') {
  return {
    /** IDs seleccionados actualmente (siempre array) */
    selected: [] as string[],

    /** Todos los IDs disponibles de la tabla */
    allIds: allIds || [],

    /** Identificador de la tabla */
    tableId,

    /** Devuelve true si todos los IDs están seleccionados */
    get allSelected() {
      return this.selected.length === this.allIds.length;
    },

    /**
     * Devuelve un solo valor si hay uno, o el array si hay más de uno
     * Ideal para contextos donde solo se necesita un ID
     */
    get selectedValue() {
      if (this.selected.length === 1) return this.selected[0];
      return [...this.selected];
    },

    /** Alterna la selección de un ID individual */
    toggle(id: string) {
      if (this.selected.includes(id)) {
        this.selected = this.selected.filter(i => i !== id);
      } else {
        this.selected.push(id);
      }
      this.emitSelectionUpdate();
    },

    /** Alterna la selección de todos los IDs */
    toggleAll() {
      this.selected = this.selected.length === this.allIds.length
        ? []
        : [...this.allIds];
      this.emitSelectionUpdate();
    },

    /** Limpia la selección */
    clear() {
      this.selected = [];
      this.emitSelectionUpdate();
    },

    /** Devuelve los IDs seleccionados (siempre array) */
    get selectedIds() {
      return [...this.selected];
    },

    /**
     * Emite un evento global con los IDs seleccionados
     * Convierte el array reactivo (Proxy) a un array plano
     * Se puede conectar a tableSelectionListener para callbacks centralizados
     */
    emitSelectionUpdate: (() => {
      let timeout: string;
      const debounceTime = 50; // ms
      return function () {
        clearTimeout(timeout);
        timeout = window.setTimeout(() => {
          window.dispatchEvent(new CustomEvent('table-selection-updated', {
            detail: {
              tableId: this.tableId,
              selectedIds: [...this.selected],
            },
          }));
        }, debounceTime);
      };
    })(),
  };
}

/**
 * Inicializa los btn-groups de todas las tablas en el DOM
 * Escucha automáticamente los cambios de selección de cada tabla
 */
export function initTableActions() {
  document.querySelectorAll<HTMLElement>('[data-table-id]').forEach(el => {
    if (el.dataset.tableInit) return; // ya inicializado
    el.dataset.tableInit = 'true';

    const tableId = el.dataset.tableId;
    if (!tableId) return;

    // Registrar callback para actualizar btn-group al cambiar selección
    onTableSelectionUpdate(tableId, (selectedIds) => {
      const btnGroup = el.querySelector<HTMLElement>(`.btn-group[data-btn-group-id="${tableId}"]`);
      if (btnGroup) btnGroup.dataset.id = selectedIds.join(',');
    });
  });
}
