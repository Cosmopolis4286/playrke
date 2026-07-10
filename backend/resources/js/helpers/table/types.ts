/**
 * @description Payload del CustomEvent `table:crud`, tal como lo emite
 * TableWithFilterBridge.vue (ver `emitTableCrudEvent`).
 */
export interface TableCrudEventDetail {
  action: 'create' | 'edit' | 'delete' | string
  tableId: string
  encryptIds?: string[] // uno o varios
}
