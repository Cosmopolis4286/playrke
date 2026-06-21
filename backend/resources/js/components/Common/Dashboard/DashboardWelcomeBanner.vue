<script setup lang="ts" name="DashboardWelcomeBanner">
  import { computed, ref, watch } from 'vue'
  import { usePage } from '@inertiajs/vue3'
  import type { WelcomeBannerProps } from '@/js/types/common/dashboard/dashboard'

  const props = withDefaults(defineProps<WelcomeBannerProps>(), {
    message: '',
    actions: () => [],
    roleKey: 'default',
  })

  const page = usePage()

  /** URL de avatar del usuario autenticado, con el mismo fallback que usa el navbar */
  const avatarUrl = computed<string | null>(() => (page.props.auth as any)?.user?.avatar_url ?? null)

  /** Si la imagen falla al cargar, se cae a las iniciales (igual que el navbar cae a fav.ico) */
  const avatarFailed = ref(false)
  watch(avatarUrl, () => { avatarFailed.value = false })

  const greeting = computed<string>(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Buenos días'
    if (hour < 19) return 'Buenas tardes'
    return 'Buenas noches'
  })

  const todayLabel = computed<string>(() =>
    new Intl.DateTimeFormat('es-VE', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())
  )

  /** Iniciales del nombre para el avatar */
  const initials = computed<string>(() => {
    const parts = props.userName.trim().split(' ')
    return parts.length >= 2
      ? (parts[0][0] + parts[1][0]).toUpperCase()
      : parts[0].slice(0, 2).toUpperCase()
  })

  /** Clave de rol normalizada para variar el acento de color del banner */
  const accentKey = computed<string>(() => {
    const known = ['superadmin', 'admin', 'treasurer', 'seller', 'commerce', 'player']
    return known.includes(props.roleKey) ? props.roleKey : 'default'
  })
</script>

<template>
  <div class="wb" :class="`wb--${accentKey}`">
    <div class="wb__body">
      <!-- Avatar con anillo de acento: foto real del usuario, o iniciales si no hay/falla -->
      <div class="wb__avatar-ring">
        <img
          v-if="avatarUrl && !avatarFailed"
          :src="avatarUrl"
          :alt="userName"
          class="wb__avatar wb__avatar--img"
          @error="avatarFailed = true"
        />
        <div v-else class="wb__avatar" aria-hidden="true">{{ initials }}</div>
        <span class="wb__avatar-dot" aria-hidden="true"></span>
      </div>

      <!-- Texto -->
      <div class="wb__text">
        <div class="wb__greeting-row">
          <p class="wb__greeting">
            {{ greeting }}, <strong>{{ userName }}</strong>
          </p>
          <span v-if="roleName" class="wb__badge">{{ roleName }}</span>
        </div>
        <p class="wb__sub">
          <i class="fas fa-calendar-day wb__sub-icon"></i>
          <span class="wb__date">{{ todayLabel }}</span>
          <span v-if="message" class="wb__sep">·</span>
          <span v-if="message">{{ message }}</span>
        </p>
      </div>

      <!-- Highlights contextuales por rol (slot) -->
      <div v-if="$slots.highlights" class="wb__highlights">
        <slot name="highlights" />
      </div>

      <!-- Acciones rápidas -->
      <div v-if="actions.length" class="wb__actions">
        <component
          v-for="(action, i) in actions"
          :key="i"
          :is="action.href ? 'a' : 'button'"
          :href="action.href"
          type="button"
          class="wb__action"
          :class="`wb__action--${action.variant ?? 'outline-primary'}`"
          @click="action.onClick?.($event)"
        >
          <i v-if="action.icon" :class="action.icon"></i>
          {{ action.label }}
        </component>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Contenedor: minimalista, fondo claro con acento sutil por rol ── */
.wb {
  position: relative;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #eef0f4;
  box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
  overflow: hidden;
}

.wb::before {
  content: '';
  position: absolute;
  top: 0; left: 0; bottom: 0;
  width: 3px;
  background: var(--wb-accent, #667eea);
}

.wb--superadmin { --wb-accent: linear-gradient(180deg, #f59e0b, #f97316); }
.wb--admin      { --wb-accent: linear-gradient(180deg, #667eea, #764ba2); }
.wb--treasurer  { --wb-accent: linear-gradient(180deg, #10b981, #059669); }
.wb--seller     { --wb-accent: linear-gradient(180deg, #ec4899, #db2777); }
.wb--commerce   { --wb-accent: linear-gradient(180deg, #3b82f6, #2563eb); }
.wb--player     { --wb-accent: linear-gradient(180deg, #8b5cf6, #6366f1); }
.wb--default    { --wb-accent: #667eea; }

.wb__body {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 1rem;
  overflow-x: auto;
  scrollbar-width: none;
}
.wb__body::-webkit-scrollbar { display: none; }

/* ── Avatar ── */
.wb__avatar-ring {
  position: relative;
  flex-shrink: 0;
  padding: 2px;
  border-radius: 50%;
  background: var(--wb-accent, #667eea);
}

.wb__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #fff;
  color: #2d3748;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: .02em;
  user-select: none;
}

.wb__avatar--img {
  object-fit: cover;
  display: block;
}

.wb__avatar-dot {
  position: absolute;
  bottom: -1px;
  right: -1px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #22c55e;
  border: 2px solid #fff;
}

/* ── Texto ── */
.wb__text { flex-shrink: 0; min-width: 0; }

.wb__greeting-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.wb__greeting {
  margin: 0;
  font-size: 0.85rem;
  font-weight: 500;
  color: #1a202c;
  line-height: 1.2;
  white-space: nowrap;
}
.wb__greeting strong { font-weight: 700; }

.wb__badge {
  display: inline-block;
  padding: 1px 8px;
  border-radius: 20px;
  background: #f1f3f9;
  color: #4a5568;
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  white-space: nowrap;
  flex-shrink: 0;
}

.wb__sub {
  margin: 1px 0 0;
  font-size: 0.68rem;
  color: #8a94a6;
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 0.25rem;
  white-space: nowrap;
}
.wb__sub-icon { font-size: 0.62rem; color: #b0b8c5; }
.wb__date { text-transform: capitalize; }
.wb__sep { color: #d1d5db; }

/* ── Highlights contextuales ── */
.wb__highlights {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
  padding: 0 0.85rem;
  border-left: 1px solid #f0f2f7;
  white-space: nowrap;
}

/* ── Acciones ── */
.wb__actions {
  display: flex;
  flex-wrap: nowrap;
  gap: 0.4rem;
  margin-left: auto;
  flex-shrink: 0;
  white-space: nowrap;
}

.wb__action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  border-radius: 7px;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.15s ease;
}
.wb__action:hover { transform: translateY(-1px); text-decoration: none; }

.wb__action--primary  { background: #667eea; color: #fff; }
.wb__action--primary:hover  { background: #5a67d8; }
.wb__action--success  { background: #10b981; color: #fff; }
.wb__action--success:hover  { background: #059669; }
.wb__action--danger   { background: #ef4444; color: #fff; }
.wb__action--danger:hover   { background: #dc2626; }
.wb__action--warning  { background: #f59e0b; color: #fff; }
.wb__action--warning:hover  { background: #d97706; }
.wb__action--info     { background: #3b82f6; color: #fff; }
.wb__action--info:hover     { background: #2563eb; }
.wb__action--secondary{ background: #f1f3f9; color: #4a5568; }
.wb__action--secondary:hover{ background: #e5e9f2; }

.wb__action--outline-primary  { border-color: #c7d2fe; color: #667eea; background: transparent; }
.wb__action--outline-primary:hover  { background: #eef0ff; }
.wb__action--outline-success  { border-color: #a7f3d0; color: #059669; background: transparent; }
.wb__action--outline-success:hover  { background: #ecfdf5; }
.wb__action--outline-danger   { border-color: #fecaca; color: #dc2626; background: transparent; }
.wb__action--outline-danger:hover   { background: #fef2f2; }
.wb__action--outline-warning  { border-color: #fde68a; color: #d97706; background: transparent; }
.wb__action--outline-warning:hover  { background: #fffbeb; }
.wb__action--outline-info     { border-color: #bfdbfe; color: #2563eb; background: transparent; }
.wb__action--outline-info:hover     { background: #eff6ff; }
.wb__action--outline-secondary{ border-color: #e2e8f0; color: #64748b; background: transparent; }
.wb__action--outline-secondary:hover{ background: #f8fafc; }

@media (max-width: 576px) {
  .wb__body { padding: 0.6rem 0.85rem; }
}
</style>
