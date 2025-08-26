// js/alpine/tableSelection.ts

// Función para inicializar los btn-groups de todas las tablas
import { onTableSelectionUpdate } from './tableSelectionListener';

export function tableSelection(allIds: number[] = [], tableId: string = 'default') {
    return {
        selected: [] as number[],
        allIds: allIds || [],
        tableId: tableId,

        get allSelected() {
            return this.selected.length === this.allIds.length;
        },

        toggle(id: number) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(i => i !== id);
            } else {
                this.selected.push(id);
            }
            this.emitSelectionUpdate();
        },

        toggleAll() {
            this.selected = this.selected.length === this.allIds.length
                ? []
                : [...this.allIds];
            this.emitSelectionUpdate();
        },

        clear() {
            this.selected = [];
            this.emitSelectionUpdate();
        },

        get selectedIds() {
            return this.selected;
        },

        emitSelectionUpdate() {
            window.dispatchEvent(new CustomEvent('table-selection-updated', {
                detail: {
                    tableId: this.tableId,
                    selectedIds: this.selected
                }
            }));
        }
    };
}

export function initTableActions() {
    document.querySelectorAll<HTMLElement>('[data-table-id]').forEach(el => {
        const tableId = el.dataset.tableId;
        if (!tableId) return;

        onTableSelectionUpdate(tableId, (selectedIds) => {
            console.log(`Tabla ${tableId} seleccionados:`, selectedIds);

            const btnGroup = el.querySelector<HTMLElement>(`.btn-group[data-btn-group-id="${tableId}"]`);
            if (btnGroup) btnGroup.dataset.id = selectedIds.join(',');
        });
    });
}
