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
    const known = ['superadmin', 'admin', 'treasurer', 'seller', 'commerce', 'subsidiary', 'customer', 'player']
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
          @click="action.onClick?.()"
        >
          <i v-if="action.icon" :class="action.icon"></i>
          {{ action.label }}
        </component>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Contenedor ── */
.wb {
  position: relative;
  background: var(--ds-surface);
  border-radius: 12px;
  border: 1px solid var(--ds-border);
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
.wb--subsidiary { --wb-accent: linear-gradient(180deg, #14b8a6, #0d9488); }
.wb--customer   { --wb-accent: linear-gradient(180deg, #f472b6, #db2777); }
.wb--player     { --wb-accent: linear-gradient(180deg, #8b5cf6, #6366f1); }
.wb--default    { --wb-accent: #667eea; }

.wb__body {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.65rem;
  padding: 0.55rem 0.85rem;
}

/* ── Avatar ── */
.wb__avatar-ring {
  position: relative;
  flex-shrink: 0;
  padding: 2px;
  border-radius: 50%;
  background: var(--wb-accent, #667eea);
}

.wb__avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--ds-surface);
  color: var(--ds-text);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: .02em;
  user-select: none;
  transition: width .15s ease, height .15s ease, font-size .15s ease;
}

/* El avatar se reduce en pantallas angostas para ceder espacio al texto
   y evitar que el saludo/badge salten de línea antes de lo necesario. */
@media (max-width: 576px) {
  .wb__avatar { width: 28px; height: 28px; font-size: 0.62rem; }
}
@media (max-width: 400px) {
  .wb__avatar { width: 24px; height: 24px; font-size: 0.56rem; }
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
  border: 2px solid var(--ds-surface);
}

/* ── Texto ── */
.wb__text { flex: 1 1 auto; min-width: 0; }

.wb__greeting-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.wb__greeting {
  margin: 0;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--ds-text);
  line-height: 1.2;
}
.wb__greeting strong { font-weight: 700; }

.wb__badge {
  display: inline-block;
  padding: 1px 7px;
  border-radius: 20px;
  background: var(--ds-badge-bg);
  color: var(--ds-badge-color);
  font-size: 0.58rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  white-space: nowrap;
  flex-shrink: 0;
}

.wb__sub {
  margin: 1px 0 0;
  font-size: 0.66rem;
  color: var(--ds-text-2);
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.25rem;
  row-gap: 2px;
}
.wb__sub-icon { font-size: 0.6rem; color: var(--ds-text-muted); }
.wb__date { text-transform: capitalize; white-space: nowrap; }
.wb__sep { color: var(--ds-border-2); }

/* ── Highlights contextuales ──
   flex-basis 100% por defecto: si no caben junto al texto en la misma
   fila, pasan a ocupar su propia línea completa (wrap del contenedor
   padre) en vez de comprimirse/recortarse compitiendo por el espacio
   restante — evita el corte/solapamiento con el saludo y las acciones. */
.wb__highlights {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem 0.85rem;
  flex: 1 1 100%;
  padding: 0.45rem 0 0;
  margin: 0;
  border-top: 1px solid var(--ds-border);
}

/* Desde md hacia arriba, si hay espacio libre en la fila del saludo,
   los highlights se acoplan ahí (columna auxiliar con separador) en vez
   de forzar su propia línea siempre. */
@media (min-width: 768px) {
  .wb__highlights {
    flex: 0 1 auto;
    width: auto;
    padding: 0 0.75rem;
    margin: 0;
    border-top: none;
    border-left: 1px solid var(--ds-border);
  }
}

/* Móvil angosto: fila horizontal con scroll en vez de apilar cada stat
   en su propia línea vertical — más compacto en pantallas chicas. */
@media (max-width: 480px) {
  .wb__highlights {
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    gap: 1.1rem;
    -webkit-overflow-scrolling: touch;
  }
  .wb__highlights::-webkit-scrollbar { display: none; }
  .wb__highlights > :deep(*) { flex-shrink: 0; }
}

/* ── Acciones ──
   Mismo criterio que .wb__highlights: en pantallas angostas ocupan su
   propia línea completa (flex-basis 100%) en vez de competir por espacio
   y cortarse; desde lg, si caben, se acoplan a la derecha de la fila. */
.wb__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  flex: 1 1 100%;
  padding-top: 0.45rem;
  border-top: 1px solid var(--ds-border);
}

@media (min-width: 992px) {
  .wb__actions {
    flex: 0 0 auto;
    width: auto;
    margin-left: auto;
    padding-top: 0;
    border-top: none;
  }
}

.wb__action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 7px;
  font-size: 0.7rem;
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
.wb__action--secondary{ background: var(--ds-badge-bg); color: var(--ds-badge-color); }
.wb__action--secondary:hover{ background: var(--ds-surface-2); }

.wb__action--outline-primary  { border-color: #c7d2fe; color: #667eea; background: transparent; }
.wb__action--outline-primary:hover  { background: rgba(102,126,234,.1); }
.wb__action--outline-success  { border-color: #a7f3d0; color: #059669; background: transparent; }
.wb__action--outline-success:hover  { background: rgba(16,185,129,.1); }
.wb__action--outline-danger   { border-color: #fecaca; color: #dc2626; background: transparent; }
.wb__action--outline-danger:hover   { background: rgba(220,38,38,.1); }
.wb__action--outline-warning  { border-color: #fde68a; color: #d97706; background: transparent; }
.wb__action--outline-warning:hover  { background: rgba(245,158,11,.1); }
.wb__action--outline-info     { border-color: #bfdbfe; color: #2563eb; background: transparent; }
.wb__action--outline-info:hover     { background: rgba(59,130,246,.1); }
.wb__action--outline-secondary{ border-color: var(--ds-border); color: var(--ds-text-2); background: transparent; }
.wb__action--outline-secondary:hover{ background: var(--ds-surface-2); }

@media (max-width: 576px) {
  .wb__body { padding: 0.5rem 0.7rem; gap: 0.4rem; }

  .wb__badge { font-size: 0.54rem; padding: 1px 6px; }

  .wb__action { padding: 4px 8px; font-size: 0.66rem; }
}
</style>
