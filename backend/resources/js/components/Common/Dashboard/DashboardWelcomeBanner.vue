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
</script>

<template>
  <div class="welcome-banner card border-0">
    <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center gap-3">
      <!-- Avatar / icono -->
      <div class="welcome-banner__avatar d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary flex-shrink-0">
        <i class="fas fa-user fs-4"></i>
      </div>

      <!-- Texto -->
      <div class="flex-grow-1">
        <h5 class="mb-0 fw-bold">
          {{ greeting }}, <span class="text-primary">{{ userName }}</span>
        </h5>
        <p class="text-muted mb-0 small">
          <span v-if="roleName" class="badge bg-secondary me-2">{{ roleName }}</span>
          <span v-if="message">{{ message }}</span>
          <span v-else>Bienvenido a tu panel de control.</span>
        </p>
      </div>

      <!-- Acciones rápidas -->
      <div v-if="actions.length" class="d-flex flex-wrap gap-2">
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
.welcome-banner {
  background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
  border-bottom: 1px solid #e9ecef !important;
}
.welcome-banner__avatar {
  width: 56px;
  height: 56px;
}
</style>
