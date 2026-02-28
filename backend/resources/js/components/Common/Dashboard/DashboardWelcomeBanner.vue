<script setup lang="ts" name="DashboardWelcomeBanner">
  import { computed } from 'vue'
  import type { WelcomeBannerProps } from '@/js/types/common/dashboard/dashboard'

  const props = withDefaults(defineProps<WelcomeBannerProps>(), {
    message: '',
    actions: () => [],
  })

  const greeting = computed<string>(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Buenos días'
    if (hour < 19) return 'Buenas tardes'
    return 'Buenas noches'
  })

  /** Iniciales del nombre para el avatar */
  const initials = computed<string>(() => {
    const parts = props.userName.trim().split(' ')
    return parts.length >= 2
      ? (parts[0][0] + parts[1][0]).toUpperCase()
      : parts[0].slice(0, 2).toUpperCase()
  })
</script>

<template>
  <div class="wb">
    <div class="wb__body">
      <!-- Avatar con iniciales -->
      <div class="wb__avatar" aria-hidden="true">{{ initials }}</div>

      <!-- Texto -->
      <div class="wb__text">
        <p class="wb__greeting">
          {{ greeting }}, <strong>{{ userName }}</strong>
        </p>
        <p class="wb__sub">
          <span v-if="roleName" class="wb__badge">{{ roleName }}</span>
          <span>{{ message || 'Bienvenido a tu panel de control.' }}</span>
        </p>
      </div>

      <!-- Acciones rápidas -->
      <div v-if="actions.length" class="wb__actions">
        <component
          v-for="(action, i) in actions"
          :key="i"
          :is="action.href ? 'a' : 'button'"
          :href="action.href"
          type="button"
          class="btn btn-sm"
          :class="`btn-${action.variant ?? 'outline-primary'}`"
          @click="action.onClick?.($event)"
        >
          <i v-if="action.icon" :class="[action.icon, 'me-1']"></i>
          {{ action.label }}
        </component>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wb {
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e9ecef;
  border-left: 4px solid #0d6efd;
}

.wb__body {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
}

/* Avatar */
.wb__avatar {
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #0d6efd;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: .02em;
  user-select: none;
}

/* Texto */
.wb__text { flex: 1; min-width: 0; }

.wb__greeting {
  margin: 0;
  font-size: 0.95rem;
  color: #212529;
  line-height: 1.3;
}

.wb__sub {
  margin: 2px 0 0;
  font-size: 0.78rem;
  color: #6c757d;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.wb__badge {
  display: inline-block;
  padding: 1px 7px;
  border-radius: 20px;
  background: #e9ecef;
  color: #495057;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .03em;
}

/* Acciones */
.wb__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-left: auto;
}
</style>
