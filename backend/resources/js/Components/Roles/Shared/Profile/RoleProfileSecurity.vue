<script setup lang="ts">
import { computed } from 'vue'
import RpCard from './RpCard.vue'
import type { RoleProfileSecurity } from '@/js/types/roles/shared/role-profile-common'

const props = defineProps<{
  security: RoleProfileSecurity
  /** En la vista admin no se ofrece "Configurar" (lleva a la cuenta del propio admin). */
  readonly?: boolean
}>()

const checks = computed(() => [
  { key: 'email', label: 'Email verificado', ok: props.security.email_verified, help: 'Necesario para recuperar tu cuenta.' },
  { key: 'pin', label: 'PIN de retiro', ok: props.security.withdrawal_pin_set, help: 'Protege los retiros de tu billetera.' },
  { key: '2fa', label: 'Verificación en dos pasos', ok: props.security.two_factor_enabled, help: 'Un código extra al iniciar sesión.' },
])

const tone = computed(() => (props.security.score >= 100 ? 'good' : props.security.score >= 60 ? 'mid' : 'low'))
const label = computed(() => ({ good: 'Cuenta protegida', mid: 'Protección media', low: 'Protección baja' })[tone.value])

/** Anillo de progreso: circunferencia de r=26. */
const CIRC = 2 * Math.PI * 26
const dash = computed(() => `${(props.security.score / 100) * CIRC} ${CIRC}`)
</script>

<template>
  <RpCard title="Seguridad" icon="fa-shield-halved">
    <div class="rp-sec-summary">
      <svg class="rp-ring" viewBox="0 0 64 64" role="img" :aria-label="`${security.score}% de protección`">
        <circle cx="32" cy="32" r="26" class="rp-ring__track" />
        <circle cx="32" cy="32" r="26" :class="['rp-ring__value', `rp-ring__value--${tone}`]" :stroke-dasharray="dash" />
        <text x="32" y="36" text-anchor="middle" class="rp-ring__text">{{ security.score }}%</text>
      </svg>
      <div>
        <strong :class="['rp-sec-label', `rp-sec-label--${tone}`]">{{ label }}</strong>
        <p v-if="security.login_streak_days > 1" class="rp-sec-streak">
          <i class="fas fa-fire"></i>{{ security.login_streak_days }} días seguidos conectado
        </p>
      </div>
    </div>

    <ul class="rp-checks">
      <li v-for="c in checks" :key="c.key" :class="['rp-check', { 'rp-check--ok': c.ok }]">
        <i :class="['fas', c.ok ? 'fa-circle-check' : 'fa-circle-exclamation']"></i>
        <div class="rp-check__text">
          <span class="rp-check__label">{{ c.label }}</span>
          <span v-if="!c.ok" class="rp-check__help">{{ c.help }}</span>
        </div>
      </li>
    </ul>

    <a v-if="!readonly && security.score < 100" href="/profile#security" class="rp-sec-cta">
      Completar seguridad <i class="fas fa-arrow-right"></i>
    </a>
  </RpCard>
</template>

<style scoped>
.rp-sec-summary { display: flex; align-items: center; gap: 0.85rem; }
.rp-ring { width: 64px; height: 64px; flex-shrink: 0; transform: rotate(-90deg); }
.rp-ring__track { fill: none; stroke: var(--ds-surface-2); stroke-width: 7; }
.rp-ring__value { fill: none; stroke-width: 7; stroke-linecap: round; transition: stroke-dasharray 0.6s ease; }
.rp-ring__value--good { stroke: #22c55e; }
.rp-ring__value--mid  { stroke: #f59e0b; }
.rp-ring__value--low  { stroke: #f43f5e; }
.rp-ring__text { transform: rotate(90deg); transform-origin: 32px 32px; font-size: 14px; font-weight: 800; fill: var(--ds-text); }
.rp-sec-label { font-size: 0.92rem; }
.rp-sec-label--good { color: #22c55e; }
.rp-sec-label--mid  { color: #f59e0b; }
.rp-sec-label--low  { color: #f43f5e; }
.rp-sec-streak { display: flex; align-items: center; gap: 0.35rem; margin: 0.15rem 0 0; font-size: 0.74rem; color: var(--ds-text-2); }
.rp-sec-streak i { color: #f97316; }

.rp-checks { list-style: none; margin: 0.9rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.55rem; }
.rp-check { display: flex; align-items: flex-start; gap: 0.55rem; }
.rp-check > i { margin-top: 0.15rem; font-size: 0.85rem; color: #f59e0b; }
.rp-check--ok > i { color: #22c55e; }
.rp-check__text { display: flex; flex-direction: column; }
.rp-check__label { font-size: 0.84rem; font-weight: 600; color: var(--ds-text); }
.rp-check--ok .rp-check__label { color: var(--ds-text-2); }
.rp-check__help { font-size: 0.72rem; color: var(--ds-text-muted); }

.rp-sec-cta {
  display: flex; align-items: center; justify-content: center; gap: 0.4rem;
  margin-top: 0.9rem; padding: 7px 12px;
  border-radius: 9px; border: 1.5px solid rgba(124, 140, 240, 0.5);
  font-size: 0.8rem; font-weight: 700; color: #7c8cf0; text-decoration: none;
}
.rp-sec-cta:hover { background: rgba(102, 126, 234, 0.1); color: #7c8cf0; text-decoration: none; }
</style>
