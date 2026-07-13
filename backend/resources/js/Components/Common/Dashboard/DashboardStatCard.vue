<script setup lang="ts" name="DashboardStatCard">
  import type { StatCardData } from '@/js/types/common/dashboard/dashboard'

  withDefaults(defineProps<StatCardData>(), {
    variant: 'primary',
    trend: 'neutral',
  })

  const trendIcon = {
    up: 'fas fa-caret-up',
    down: 'fas fa-caret-down',
    neutral: '',
  } as const

  const trendClass = {
    up: 'sc-trend--up',
    down: 'sc-trend--down',
    neutral: '',
  } as const
</script>

<template>
  <component
    :is="href ? 'a' : 'div'"
    :href="href"
    class="sc"
    :class="[`sc--${variant}`, href ? 'sc--link' : '']"
  >
    <div class="sc__stripe" :class="`sc__stripe--${variant}`"></div>

    <div class="sc__body">
      <!-- Icono -->
      <div v-if="icon" class="sc__icon" :class="`sc__icon--${variant}`">
        <i :class="icon"></i>
      </div>

      <!-- Contenido -->
      <div class="sc__content">
        <span class="sc__label">{{ label }}</span>
        <span class="sc__value">{{ value }}</span>
        <span v-if="description" class="sc__desc">{{ description }}</span>

        <span
          v-if="trend && trend !== 'neutral' && trendValue !== undefined"
          class="sc__trend"
          :class="trendClass[trend]"
        >
          <i v-if="trendIcon[trend]" :class="trendIcon[trend]"></i>
          {{ trendValue }}%
          <span v-if="trendLabel" class="sc__trend-lbl">{{ trendLabel }}</span>
        </span>
      </div>
    </div>
  </component>
</template>

<style scoped>
.sc {
  position: relative;
  background: var(--ds-surface);
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
  text-decoration: none;
  color: var(--ds-text);
  transition: box-shadow 0.18s, transform 0.18s;
}
.sc--link:hover {
  box-shadow: var(--ds-card-hover-shadow);
  transform: translateY(-2px);
}

/* Franja superior */
.sc__stripe { height: 4px; }
.sc__stripe--primary   { background: #0d6efd; }
.sc__stripe--success   { background: #198754; }
.sc__stripe--danger    { background: #dc3545; }
.sc__stripe--warning   { background: #ffc107; }
.sc__stripe--info      { background: #0dcaf0; }
.sc__stripe--secondary { background: #6c757d; }

.sc__body {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.9rem 1rem;
}

/* Icono */
.sc__icon {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}
.sc__icon--primary   { background: rgba(13,110,253,.13); color: #0d6efd; }
.sc__icon--success   { background: rgba(25,135,84,.13);  color: #198754; }
.sc__icon--danger    { background: rgba(220,53,69,.13);  color: #dc3545; }
.sc__icon--warning   { background: rgba(255,193,7,.13);  color: #b08000; }
.sc__icon--info      { background: rgba(13,202,240,.13); color: #0aa2c0; }
.sc__icon--secondary { background: var(--ds-surface-2);  color: var(--ds-text-2); }

/* Contenido */
.sc__content { display: flex; flex-direction: column; gap: 1px; min-width: 0; }

.sc__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--ds-text-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sc__value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.15;
  color: var(--ds-text);
}
.sc__desc { font-size: 0.76rem; color: var(--ds-text-muted); }

/* Sombra en tema claro para separar la card del fondo */
:root:not([data-theme="light"]) .sc {
  box-shadow: none;
}
:global([data-theme="light"]) .sc {
  box-shadow: 0 1px 4px rgba(0,0,0,.07), 0 0 0 1px rgba(0,0,0,.04);
}
:global([data-theme="light"]) .sc--link:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,.12);
}

/* Tendencia */
.sc__trend {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 0.73rem;
  font-weight: 600;
  margin-top: 2px;
}
.sc-trend--up   { color: #198754; }
.sc-trend--down { color: #dc3545; }
.sc__trend-lbl  { font-weight: 400; color: var(--ds-text-muted); }
</style>
