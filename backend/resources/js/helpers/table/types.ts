/**
 * @description Payload canónico para acciones CRUD disparadas desde tabla/UI
 */
export interface TableCrudEventDetail {
  action: 'create' | 'edit' | 'delete' | string
  moduleId: string
  tableId: string
  encryptIds?: string[] // uno o varios
}
