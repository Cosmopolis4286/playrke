<script setup lang="ts">
/**
 * Header de página reutilizable: ícono en badge + título + subtítulo +
 * breadcrumb, con un slot de acciones a la derecha (ej. "Actualizado hh:mm",
 * un botón, o nada). Extraído de Jobs/Index.vue — antes de agregarlo a una
 * página nueva, confirma que su header no necesita el layout AdminLTE
 * clásico (content-header/container-fluid) que sigue usando la mayoría de
 * las páginas admin del proyecto; este componente es para el patrón nuevo.
 */
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'

interface BreadcrumbItem {
  label: string
  route?: string
  icon?: string
}

withDefaults(defineProps<{
  icon: string
  title: string
  subtitle?: string
  breadcrumbItems?: BreadcrumbItem[]
}>(), {
  subtitle: undefined,
  breadcrumbItems: () => [],
})
</script>

<template>
  <div class="ph-wrap">
    <div v-if="breadcrumbItems.length > 0" class="ph-breadcrumb-row">
      <Breadcrumb :items="breadcrumbItems" />
    </div>

    <div class="ph-title-row">
      <div class="ph-title-block">
        <span class="ph-title-icon"><i class="fas" :class="icon"></i></span>
        <div class="ph-title-text">
          <h2 class="ph-title">{{ title }}</h2>
          <p v-if="subtitle" class="ph-subtitle">{{ subtitle }}</p>
        </div>
      </div>
      <div v-if="$slots.actions" class="ph-actions">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.ph-wrap { margin-bottom: 1.5rem; }

/* Breadcrumb.vue usa float-sm-right internamente (Bootstrap) — sin un
   contenedor con clearfix propio, flota fuera del flujo normal y se
   superpone visualmente con la fila de título de abajo. */
.ph-breadcrumb-row { display: flow-root; margin-bottom: 0.5rem; }

.ph-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding-bottom: 1.1rem;
  border-bottom: 1px solid var(--ds-border);
}
.ph-title-block { display: flex; align-items: center; gap: 0.85rem; min-width: 0; }
.ph-title-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  background: linear-gradient(135deg, #667eea22, #764ba222);
  color: #667eea;
}
.ph-title-text { min-width: 0; }
.ph-title { font-size: 1.3rem; font-weight: 700; color: var(--ds-text); margin: 0; letter-spacing: -0.01em; }
.ph-subtitle { font-size: 0.85rem; color: var(--ds-text-muted); margin: 0.2rem 0 0; }
.ph-actions { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; }
</style>
