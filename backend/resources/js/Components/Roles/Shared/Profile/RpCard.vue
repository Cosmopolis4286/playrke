<script setup lang="ts">
/**
 * Tarjeta base de la ficha de rol ("Mi Comercio/Agencia/Perfil"). Cada
 * sección de la ficha es un RpCard con sus propios estilos — antes las
 * secciones compartidas (RoleProfileExtras) dependían de clases .sd-* del
 * <style scoped> de cada página, que Vue no propaga a componentes hijos, y
 * se veían sin estilos (títulos como <h4> crudos, "Comisión5%").
 */
defineProps<{
  title: string
  icon: string
  subtitle?: string
}>()
</script>

<template>
  <section class="rp-card">
    <header class="rp-card__header">
      <span class="rp-card__icon"><i :class="['fas', icon]" aria-hidden="true"></i></span>
      <div class="rp-card__heading">
        <h2 class="rp-card__title">{{ title }}</h2>
        <p v-if="subtitle" class="rp-card__subtitle">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="rp-card__actions"><slot name="actions" /></div>
    </header>
    <div class="rp-card__body"><slot /></div>
  </section>
</template>

<style scoped>
.rp-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  padding: 1.1rem 1.25rem 1.25rem;
  min-width: 0;
}
.rp-card__header { display: flex; align-items: center; gap: 0.7rem; margin-bottom: 1rem; }
.rp-card__icon {
  width: 34px; height: 34px; flex-shrink: 0;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 10px;
  background: rgba(102, 126, 234, 0.14);
  color: #7c8cf0;
  font-size: 0.9rem;
}
.rp-card__heading { flex: 1; min-width: 0; }
.rp-card__title { margin: 0; font-size: 0.98rem; font-weight: 700; color: var(--ds-text); line-height: 1.25; }
.rp-card__subtitle { margin: 0.1rem 0 0; font-size: 0.76rem; color: var(--ds-text-muted); }
.rp-card__actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
</style>
