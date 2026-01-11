<template>
  <TableWithFilter
    :table-id="tableId"
    :title="title"
    :columns="parsedColumns"
    :rows="parsedRows"
    :filters="parsedFilters"
    :search-placeholder="searchPlaceholder"
    :debounce-delay="debounceDelay"
    :empty-message="emptyMessage"
    :show-create-button="showCreateButton"
    :show-edit-button="showEditButton"
    :show-delete-button="showDeleteButton"
    @create="handleCreate"
    @edit="handleEdit"
    @delete="handleDelete"
    @selection-change="handleSelectionChange"
  >
    <!-- Reenvío de todos los slots recibidos -->
    <template v-for="(_, name) in $slots" #[name]="slotData" :key="name">
      <slot :name="name" v-bind="slotData" />
    </template>

    <!-- Botones adicionales a la derecha del eliminar -->
    <template #action-buttons="{ selectedIds }">
      <button
        v-for="(btn, i) in visibleExtraButtons"
        :key="i"
        :class="['btn', 'btn-sm', btn.class || 'btn-secondary', 'ml-2']"
        :disabled="btn.disabled ? btn.disabled(selectedIds) : false"
        @click="() => btn.onClick(selectedIds)"
        type="button"
      >
        <i v-if="btn.icon" :class="btn.icon"></i>
        {{ btn.label }}
        <span v-if="shouldShowCount(btn, selectedIds)"> ({{ selectedIds.length }}) </span>
      </button>
    </template>
  </TableWithFilter>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import TableWithFilter from './TableWithFilter.vue'

  /**
   * Estructura para los botones extra que se muestran en la tabla.
   */
  interface ExtraButton {
    /** Texto visible en el botón */
    label: string
    /** Clases CSS para ícono (FontAwesome, etc.) */
    icon?: string
    /** Clases CSS para el botón (ej: 'btn-info') */
    class?: string
    /**
     * Controla si el botón se muestra.
     * Puede ser booleano o función que recibe IDs seleccionados.
     */
    show?: boolean | ((selectedIds: string[]) => boolean)
    /**
     * Controla si el botón está deshabilitado.
     * Recibe IDs seleccionados y retorna booleano.
     */
    disabled?: (selectedIds: string[]) => boolean
    /** Nombre de la acción que realizará el botón */
    action?: string
    /**
     * Función que se ejecuta al hacer click en el botón.
     * Recibe los IDs seleccionados.
     */
    onClick: (selectedIds: string[]) => void
  }

  /** Props recibidas desde el componente padre */
  const props = defineProps({
    tableId: { type: String, required: true },
    title: { type: String, required: true },
    columns: { type: [Array, String], required: true },
    rows: { type: [Array, String], required: true },
    filters: { type: [Array, String], default: () => [] },
    searchPlaceholder: { type: String, default: 'Buscar en la tabla...' },
    debounceDelay: { type: Number, default: 300 },
    emptyMessage: { type: String, default: 'No se encontraron registros' },
    showCreateButton: { type: Boolean, default: true },
    showEditButton: { type: Boolean, default: true },
    showDeleteButton: { type: Boolean, default: true },
    moduleId: { type: String, default: '' },
    context: { type: String, default: '' },
    extraButtons: {
      type: Array as () => ExtraButton[],
      default: () => []
    }
  })

  /** IDs seleccionados actualmente en la tabla */
  const selectedIds = ref<string[]>([])

  /** Computed para parsear columnas, filas y filtros si vienen como JSON string */
  const parsedColumns = computed(() =>
    typeof props.columns === 'string' ? JSON.parse(props.columns) : props.columns
  )
  const parsedRows = computed(() =>
    typeof props.rows === 'string' ? JSON.parse(props.rows) : props.rows
  )
  const parsedFilters = computed(() =>
    typeof props.filters === 'string' ? JSON.parse(props.filters) : props.filters
  )

  const shouldShowCount = (btn: ExtraButton, selectedIds: Array<String>) =>
    btn.action === 'changeStatus' && selectedIds.length > 1

  /**
   * Tipos de requisitos para cantidad de selección en acciones CRUD.
   * - 'any': acepta cualquier cantidad (incluso 0, salvo 'create')
   * - 'single': solo 1 seleccionado
   * - 'multiple': 2 o más seleccionados
   */
  type SelectionRequirement = 'any' | 'single' | 'multiple'

  /**
   * Opciones para la función handleCrudAction.
   */
  interface CrudActionOptions {
    /** Nombre de la acción a emitir en el evento */
    action: string
    /** Requisito de selección para la acción (default 'any') */
    selectionRequirement?: SelectionRequirement
    /**
     * Función previa al emit, puede cancelar la acción
     * Retorna false para cancelar emisión
     */
    onBeforeEmit?: (ids: string[]) => boolean
    /** Mensaje de log opcional para consola */
    logMessage?: string
    /** IDs sobre los que operar (por defecto usa selectedIds.value) */
    ids?: string[]
  }

  /**
   * Emite evento 'table:crud' con la acción y IDs dados.
   * @param action Acción (string) para enviar
   * @param ids Array de IDs seleccionados (default: selectedIds.value)
   */
  function emitTableCrudEvent(action: string, ids: string[] = selectedIds.value) {
    const encryptId = ids.length === 1 ? ids[0] : ''
    window.dispatchEvent(
      new CustomEvent('table:crud', {
        detail: {
          action,
          moduleId: props.moduleId,
          tableId: props.tableId,
          encryptId,
          context: props.context
        }
      })
    )
  }

  /**
   * Handler genérico para acciones CRUD con validación de selección,
   * logging y posibilidad de cancelar antes de emitir el evento.
   *
   * @param options Configuración de la acción
   */
  function handleCrudAction({
    action,
    selectionRequirement = 'any',
    onBeforeEmit,
    logMessage,
    ids = selectedIds.value
  }: CrudActionOptions) {
    const count = ids.length

    // Validar cantidad de IDs según requisito
    if (
      (selectionRequirement === 'single' && count !== 1) ||
      (selectionRequirement === 'multiple' && count < 2) ||
      (selectionRequirement === 'any' && count === 0 && action !== 'create')
    ) {
      console.warn(
        `La acción '${action}' requiere selección ${selectionRequirement}, pero hay ${count} seleccionados.`
      )
      return
    }

    // Log si está definido
    if (logMessage) {
      console.log(logMessage, ids)
    }

    // Ejecutar función previa a emitir, cancelar si retorna false
    if (onBeforeEmit && !onBeforeEmit(ids)) {
      console.log(`Acción '${action}' cancelada por onBeforeEmit.`)
      return
    }

    // Emitir evento con acción e IDs
    emitTableCrudEvent(action, ids)
  }

  /** Handlers estándar para botones CRUD, usando el handler genérico */

  /** Crear nuevo registro (sin IDs) */
  const handleCreate = () => emitTableCrudEvent('create')

  /** Editar registro seleccionado (debe haber uno solo) */
  const handleEdit = () => handleCrudAction({ action: 'edit', selectionRequirement: 'single' })

  /** Eliminar uno o varios registros seleccionados */
  const handleDelete = () => handleCrudAction({ action: 'delete', selectionRequirement: 'any' })

  /** Cambiar estado para uno o varios registros seleccionados */
  const handleChangeStatus = () =>
    handleCrudAction({
      action: 'changeStatus',
      selectionRequirement: 'any',
      logMessage: 'Ejecutando cambio de estado para IDs:'
    })

  /** Resetear contraseña para uno o varios registros seleccionados */
  const handleResetPassword = () =>
    handleCrudAction({
      action: 'resetPassword',
      selectionRequirement: 'any',
      logMessage: 'Ejecutando reseteo de contraseña para IDs:'
    })

  /**
   * Extrae los botones extras y asigna el método onClick
   * adecuado, basado en la propiedad 'action' del botón.
   */
  const extraButtonsWithHandlers = computed(() => {
    return props.extraButtons.map(btn => {
      /**
       * Función ejecutada al click, recibe IDs seleccionados.
       * Usa el handler genérico para validar y emitir eventos.
       */
      const onClick = (ids: string[]) => {
        switch (btn.action) {
          case 'changeStatus':
            handleChangeStatus()
            break
          case 'resetPassword':
            handleResetPassword()
            break
          default:
            console.warn(`No handler definido para el botón '${btn.label}'`)
        }
      }

      const disabled = (selectedIds: string[]) => {
        if (btn.action === 'resetPassword') {
          return selectedIds.length !== 1
        }
        return btn.disabled ? btn.disabled(selectedIds) : false
      }

      return {
        ...btn,
        disabled,
        onClick
      }
    })
  })

  /**
   * Filtra y retorna solo los botones extras visibles según
   * el valor booleano o función `show`.
   */
  const visibleExtraButtons = computed(() =>
    extraButtonsWithHandlers.value.filter(btn => {
      if (!btn) return false
      if (btn.show === undefined) return true
      if (typeof btn.show === 'function') return btn.show(selectedIds.value)
      if (btn.show === false) return selectedIds.value.length > 0
      return !!btn.show
    })
  )

  /**
   * Actualiza el array reactivo de IDs seleccionados
   * y emite evento global para sincronizar subsistemas.
   * @param ids IDs seleccionados actualmente
   */
  const handleSelectionChange = (ids: string[]): void => {
    selectedIds.value = [...ids]
    if (import.meta.env.DEV) {
      console.log('🔁 table-selection-updated', {
        tableId: props.tableId,
        selectedIds: selectedIds.value
      })
    }
    window.dispatchEvent(
      new CustomEvent('table-selection-updated', {
        detail: {
          tableId: props.tableId,
          selectedIds: [...selectedIds.value],
          context: props.context
        }
      })
    )
  }
</script>
