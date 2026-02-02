<script setup lang="ts" name="TableFooterInfo">
  import { computed } from 'vue'

  /**
   * Props del componente TableFooterInfo
   */
  const props = defineProps<{
    /**
     * Total de filas luego de aplicar filtros
     */
    filteredCount: number

    /**
     * Total de filas originales
     */
    totalCount: number

    /**
     * Cantidad de filas seleccionadas
     */
    selectedCount?: number
  }>()

  /**
   * Indica si hay filas para mostrar el footer
   */
  const hasRows = computed(() => props.filteredCount > 0)
</script>

<template>
  <!-- Footer visible solo si hay registros -->
  <div v-if="hasRows" class="d-flex justify-content-between align-items-center mt-2">
    <small class="text-muted">
      Mostrando {{ filteredCount }} de {{ totalCount }} registros

      <!-- Información de selección -->
      <span v-if="selectedCount && selectedCount > 0"> ({{ selectedCount }} seleccionados) </span>
    </small>
  </div>
</template>
