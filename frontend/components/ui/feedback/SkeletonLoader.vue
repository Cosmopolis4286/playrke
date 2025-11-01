<template>
  <div
    class="skeleton-loader"
    :class="skeletonClasses"
    :style="skeletonStyles"
    role="status"
    :aria-label="ariaLabel"
    :aria-busy="true"
  >
    <!-- Custom skeleton content -->
    <slot v-if="$slots.default" />

    <!-- Predefined skeleton variants -->
    <template v-else>
      <!-- Text skeleton -->
      <template v-if="variant === 'text'">
        <div
          v-for="line in lines"
          :key="line"
          class="skeleton-line"
          :style="getLineWidth(line)"
        />
      </template>

      <!-- Avatar skeleton -->
      <div v-else-if="variant === 'avatar'" class="skeleton-avatar" />

      <!-- Card skeleton -->
      <template v-else-if="variant === 'card'">
        <div class="skeleton-card">
          <div class="skeleton-card-header">
            <div class="skeleton-avatar" />
            <div class="skeleton-text-block">
              <div class="skeleton-line" style="width: 60%" />
              <div class="skeleton-line" style="width: 40%" />
            </div>
          </div>
          <div class="skeleton-card-content">
            <div class="skeleton-line" style="width: 100%" />
            <div class="skeleton-line" style="width: 80%" />
            <div class="skeleton-line" style="width: 90%" />
          </div>
          <div class="skeleton-card-footer">
            <div class="skeleton-button" />
            <div class="skeleton-button" />
          </div>
        </div>
      </template>

      <!-- Table skeleton -->
      <template v-else-if="variant === 'table'">
        <div class="skeleton-table">
          <div class="skeleton-table-header">
            <div v-for="col in columns" :key="col" class="skeleton-cell" />
          </div>
          <div
            v-for="row in rows"
            :key="row"
            class="skeleton-table-row"
          >
            <div v-for="col in columns" :key="col" class="skeleton-cell" />
          </div>
        </div>
      </template>

      <!-- List skeleton -->
      <template v-else-if="variant === 'list'">
        <div
          v-for="item in items"
          :key="item"
          class="skeleton-list-item"
        >
          <div class="skeleton-avatar" />
          <div class="skeleton-text-block">
            <div class="skeleton-line" style="width: 70%" />
            <div class="skeleton-line" style="width: 50%" />
          </div>
        </div>
      </template>

      <!-- Form skeleton -->
      <template v-else-if="variant === 'form'">
        <div class="skeleton-form">
          <div
            v-for="field in fields"
            :key="field"
            class="skeleton-field"
          >
            <div class="skeleton-label" />
            <div class="skeleton-input" />
          </div>
          <div class="skeleton-actions">
            <div class="skeleton-button skeleton-button--primary" />
            <div class="skeleton-button" />
          </div>
        </div>
      </template>

      <!-- Chart skeleton -->
      <template v-else-if="variant === 'chart'">
        <div class="skeleton-chart">
          <div class="skeleton-chart-bars">
            <div
              v-for="bar in 8"
              :key="bar"
              class="skeleton-bar"
              :style="{ height: `${Math.random() * 60 + 20}%` }"
            />
          </div>
          <div class="skeleton-chart-legend">
            <div v-for="item in 3" :key="item" class="skeleton-legend-item">
              <div class="skeleton-legend-color" />
              <div class="skeleton-line" style="width: 80px" />
            </div>
          </div>
        </div>
      </template>

      <!-- Default rectangle skeleton -->
      <div v-else class="skeleton-rect" />
    </template>

    <!-- Shimmer overlay -->
    <div v-if="animate" class="skeleton-shimmer" />
  </div>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'text' | 'avatar' | 'card' | 'table' | 'list' | 'form' | 'chart' | 'rect'
  width?: string | number
  height?: string | number
  lines?: number
  columns?: number
  rows?: number
  items?: number
  fields?: number
  animate?: boolean
  rounded?: boolean | 'sm' | 'md' | 'lg' | 'xl' | 'full'
  speed?: 'slow' | 'normal' | 'fast'
  opacity?: number
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'rect',
  width: '100%',
  height: '1rem',
  lines: 3,
  columns: 4,
  rows: 5,
  items: 3,
  fields: 4,
  animate: true,
  rounded: false,
  speed: 'normal',
  opacity: 0.1,
  ariaLabel: 'Cargando contenido...',
})

// Composables
const { prefersReducedMotion } = useAccessibility()

// Computed
const skeletonClasses = computed(() => ({
  [`skeleton--${props.variant}`]: true,
  [`skeleton--speed-${props.speed}`]: props.animate && !prefersReducedMotion.value,
  [`skeleton--rounded-${props.rounded}`]: props.rounded && props.rounded !== true,
  'skeleton--rounded': props.rounded === true,
  'skeleton--animated': props.animate && !prefersReducedMotion.value,
  'skeleton--static': !props.animate || prefersReducedMotion.value,
}))

const skeletonStyles = computed(() => ({
  'width': typeof props.width === 'number' ? `${props.width}px` : props.width,
  'height': typeof props.height === 'number' ? `${props.height}px` : props.height,
  '--skeleton-opacity': props.opacity,
}))

// Methods
const getLineWidth = (lineNumber: number) => {
  const widths = ['100%', '80%', '60%', '90%', '70%']
  const index = (lineNumber - 1) % widths.length
  return { width: widths[index] }
}

// Performance optimization: Use different animation speeds based on device capabilities
const { isSlowConnection } = usePerformance()
const effectiveSpeed = computed(() => {
  if (prefersReducedMotion.value) return 'static'
  if (isSlowConnection.value) return 'slow'
  return props.speed
})
</script>

<style scoped>
/* Base skeleton styles */
.skeleton-loader {
  --skeleton-bg: var(--surface-secondary);
  --skeleton-shimmer: var(--surface-tertiary);
  --skeleton-opacity: 0.1;
  --skeleton-radius: var(--radius-sm);

  position: relative;
  overflow: hidden;
  background: var(--skeleton-bg);
  opacity: var(--skeleton-opacity);
}

/* Animation variants */
.skeleton--animated {
  animation: skeleton-pulse 2s ease-in-out infinite;
}

.skeleton--speed-slow.skeleton--animated {
  animation-duration: 3s;
}

.skeleton--speed-fast.skeleton--animated {
  animation-duration: 1s;
}

.skeleton--static {
  animation: none;
}

/* Rounded variants */
.skeleton--rounded {
  border-radius: var(--radius-md);
}

.skeleton--rounded-sm {
  border-radius: var(--radius-sm);
}

.skeleton--rounded-md {
  border-radius: var(--radius-md);
}

.skeleton--rounded-lg {
  border-radius: var(--radius-lg);
}

.skeleton--rounded-xl {
  border-radius: var(--radius-xl);
}

.skeleton--rounded-full {
  border-radius: var(--radius-full);
}

/* Shimmer effect */
.skeleton-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    var(--skeleton-shimmer) 50%,
    transparent 100%
  );
  transform: translateX(-100%);
  animation: skeleton-shimmer 2s ease-in-out infinite;
}

.skeleton--speed-slow .skeleton-shimmer {
  animation-duration: 3s;
}

.skeleton--speed-fast .skeleton-shimmer {
  animation-duration: 1s;
}

.skeleton--static .skeleton-shimmer {
  display: none;
}

/* Text skeleton */
.skeleton-line {
  height: 1rem;
  background: var(--skeleton-bg);
  border-radius: var(--radius-sm);
  margin-bottom: 0.5rem;
}

.skeleton-line:last-child {
  margin-bottom: 0;
}

/* Avatar skeleton */
.skeleton-avatar {
  width: 40px;
  height: 40px;
  background: var(--skeleton-bg);
  border-radius: var(--radius-full);
  flex-shrink: 0;
}

/* Card skeleton */
.skeleton-card {
  padding: var(--space-md);
  background: var(--surface-primary);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.skeleton-card-header {
  display: flex;
  gap: var(--space-sm);
  align-items: center;
}

.skeleton-text-block {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.skeleton-card-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-card-footer {
  display: flex;
  gap: var(--space-sm);
  justify-content: flex-end;
}

.skeleton-button {
  height: 36px;
  width: 80px;
  background: var(--skeleton-bg);
  border-radius: var(--radius-md);
}

.skeleton-button--primary {
  width: 120px;
}

/* Table skeleton */
.skeleton-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.skeleton-table-header,
.skeleton-table-row {
  display: flex;
  gap: var(--space-sm);
  padding: var(--space-sm);
  border-bottom: 1px solid var(--border-subtle);
}

.skeleton-table-header {
  background: var(--surface-secondary);
}

.skeleton-cell {
  flex: 1;
  height: 1.5rem;
  background: var(--skeleton-bg);
  border-radius: var(--radius-sm);
}

/* List skeleton */
.skeleton-list-item {
  display: flex;
  gap: var(--space-sm);
  align-items: center;
  padding: var(--space-sm);
  border-bottom: 1px solid var(--border-subtle);
}

.skeleton-list-item:last-child {
  border-bottom: none;
}

/* Form skeleton */
.skeleton-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.skeleton-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.skeleton-label {
  width: 100px;
  height: 1rem;
  background: var(--skeleton-bg);
  border-radius: var(--radius-sm);
}

.skeleton-input {
  height: 40px;
  background: var(--skeleton-bg);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-subtle);
}

.skeleton-actions {
  display: flex;
  gap: var(--space-sm);
  justify-content: flex-end;
  margin-top: var(--space-md);
}

/* Chart skeleton */
.skeleton-chart {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  height: 200px;
}

.skeleton-chart-bars {
  display: flex;
  gap: var(--space-xs);
  align-items: end;
  flex: 1;
  padding: var(--space-sm);
}

.skeleton-bar {
  flex: 1;
  min-height: 20px;
  background: var(--skeleton-bg);
  border-radius: var(--radius-sm);
}

.skeleton-chart-legend {
  display: flex;
  gap: var(--space-md);
  justify-content: center;
  flex-wrap: wrap;
}

.skeleton-legend-item {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.skeleton-legend-color {
  width: 12px;
  height: 12px;
  background: var(--skeleton-bg);
  border-radius: var(--radius-sm);
}

/* Default rectangle */
.skeleton-rect {
  width: 100%;
  height: 100%;
  background: var(--skeleton-bg);
  border-radius: var(--skeleton-radius);
}

/* Animations */
@keyframes skeleton-pulse {
  0%, 100% {
    opacity: var(--skeleton-opacity);
  }
  50% {
    opacity: calc(var(--skeleton-opacity) * 2);
  }
}

@keyframes skeleton-shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

/* Dark mode adjustments */
.dark {
  --skeleton-bg: var(--surface-tertiary);
  --skeleton-shimmer: var(--surface-quaternary);
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .skeleton-loader {
    --skeleton-opacity: 0.3;
    border: 1px solid var(--border-default);
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .skeleton-loader,
  .skeleton-shimmer {
    animation: none !important;
  }

  .skeleton-shimmer {
    display: none;
  }
}

/* Print styles */
@media print {
  .skeleton-loader {
    display: none;
  }
}

/* Performance optimizations */
.skeleton-loader {
  /* Hardware acceleration */
  transform: translateZ(0);
  will-change: opacity;
}

.skeleton-shimmer {
  /* Optimize shimmer animation */
  will-change: transform;
}
</style>
