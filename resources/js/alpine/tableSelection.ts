export function tableSelection(allIds: number[] = []) {
    return {
        selected: [] as number[],
        allIds: allIds || [],

        get allSelected() {
            return this.selected.length === this.allIds.length;
        },

        toggle(id: number) {
            this.selected.includes(id)
                ? this.selected = this.selected.filter(i => i !== id)
                : this.selected.push(id);
        },

        toggleAll() {
            this.selected = this.selected.length === this.allIds.length ? [] : [...this.allIds];
        },

        clear() {
            this.selected = [];
        },

        get selectedIds() {
            return this.selected;
        }
    };
}