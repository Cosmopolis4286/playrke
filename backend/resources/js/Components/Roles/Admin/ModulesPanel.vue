<template>
  <div class="modules-panel">
    <div class="modules-panel-header">
      <h2 class="modules-panel-title">Módulos del sistema</h2>
      <p class="modules-panel-desc">
        Apagar un módulo aquí lo oculta del sidebar de <strong>todos</strong> los usuarios, incluido SuperAdmin.
        <strong class="text-warning">No bloquea el acceso real:</strong> quien ya tenga la URL directa
        puede seguir usándolo, porque esto solo controla el menú, no los permisos de backend.
        Para revocar acceso de verdad usa la Matriz de Permisos.
      </p>
    </div>

    <div class="modules-grid">
      <label
        v-for="module in modules"
        :key="module.id"
        class="module-switch"
        :class="{ 'is-busy': busyId === module.id }"
      >
        <input
          type="checkbox"
          :checked="module.is_active"
          :disabled="busyId === module.id"
          @change="handleToggle(module)"
        />
        <span class="module-switch-track"><span class="module-switch-thumb"></span></span>
        <span class="module-switch-label">{{ module.label }}</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { SystemModule } from '@/js/types/roles/admin/module'
import Swal from 'sweetalert2'

defineProps<{
  modules: SystemModule[]
}>()

const emit = defineEmits<{
  (e: 'toggle', moduleId: string): void
}>()

const busyId = ref<string | null>(null)

async function handleToggle(module: SystemModule) {
  if (busyId.value) return

  if (module.is_active) {
    const result = await Swal.fire({
      title: 'Apagar módulo',
      html: `<p class="mb-0">"${module.label}" se ocultará del menú para <strong>todos</strong> los usuarios, incluido SuperAdmin, hasta que lo reactives. Esto <strong>no</strong> revoca el acceso por URL directa — para eso usa la Matriz de Permisos.</p>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, apagar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!result.isConfirmed) return
  }

  busyId.value = module.id
  try {
    emit('toggle', module.id)
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
.modules-panel {
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  padding: 1.1rem 1.25rem;
  margin-bottom: 1rem;
}

.modules-panel-header { margin-bottom: 0.9rem; }
.modules-panel-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 0.25rem;
}
.modules-panel-desc {
  font-size: 0.82rem;
  color: var(--ds-text-muted);
  margin: 0;
  line-height: 1.5;
}

.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 0.6rem;
}

.module-switch {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.55rem 0.75rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface-2);
  cursor: pointer;
  user-select: none;
  transition: border-color 0.15s;
}
.module-switch:hover { border-color: var(--ds-border-2); }
.module-switch.is-busy { opacity: 0.6; cursor: not-allowed; }
.module-switch input { position: absolute; opacity: 0; width: 0; height: 0; }

.module-switch-track {
  position: relative;
  width: 34px;
  height: 20px;
  border-radius: 999px;
  background: var(--ds-border-2);
  flex-shrink: 0;
  transition: background 0.15s;
}
.module-switch-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.25);
  transition: transform 0.15s;
}
.module-switch input:checked + .module-switch-track {
  background: #34d399;
}
.module-switch input:checked + .module-switch-track .module-switch-thumb {
  transform: translateX(14px);
}

.module-switch-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--ds-text);
}
</style>
