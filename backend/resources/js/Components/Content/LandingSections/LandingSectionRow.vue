<script setup lang="ts">
import type { LandingSectionAdmin } from '@js/types/content/landing-section'

defineProps<{
  section: LandingSectionAdmin
  saving: boolean
}>()

const emit = defineEmits<{
  toggle: [section: LandingSectionAdmin]
  edit: [section: LandingSectionAdmin]
}>()
</script>

<template>
  <div class="lsr-row" :class="{ 'lsr-row--inactive': section.status === 'INACTIVE' }">
    <span class="lsr-drag drag-handle" title="Arrastra para reordenar">
      <i class="fas fa-grip-vertical" />
    </span>

    <div class="lsr-info">
      <span class="lsr-label">{{ section.label }}</span>
      <span class="lsr-key">{{ section.section_key }}</span>
    </div>

    <span class="badge" :class="section.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'">
      {{ section.status === 'ACTIVE' ? 'Activa' : 'Inactiva' }}
    </span>

    <div class="form-check form-switch lsr-switch">
      <input
        type="checkbox"
        class="form-check-input"
        role="switch"
        :checked="section.status === 'ACTIVE'"
        :disabled="saving"
        @change="emit('toggle', section)"
      />
    </div>

    <button
      type="button"
      class="btn btn-sm btn-outline-primary"
      :disabled="Object.keys(section.content).length === 0"
      :title="Object.keys(section.content).length === 0 ? 'Esta sección no tiene contenido editable' : 'Editar contenido'"
      @click="emit('edit', section)"
    >
      <i class="fas fa-pen" /> Editar
    </button>
  </div>
</template>

<style scoped>
.lsr-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: #fff;
  margin-bottom: 8px;
}
.lsr-row--inactive {
  opacity: 0.6;
}
.lsr-drag {
  cursor: grab;
  color: #adb5bd;
  padding: 4px;
}
.lsr-drag:active {
  cursor: grabbing;
}
.lsr-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.lsr-label {
  font-weight: 600;
  font-size: 14px;
  color: #212529;
}
.lsr-row--inactive .lsr-label {
  color: #6c757d;
}
.lsr-key {
  font-size: 11px;
  color: #adb5bd;
  font-family: monospace;
}
.lsr-switch {
  margin: 0;
}
</style>
