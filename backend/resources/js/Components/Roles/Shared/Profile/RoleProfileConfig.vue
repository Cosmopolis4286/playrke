<script setup lang="ts">
/**
 * Configuración de las fichas sin red comercial (Customer, Treasurer):
 * comisión, monedas y juegos habilitados — la red comercial usa
 * RoleProfileCommission (cadena jerárquica + reparto en cascada).
 */
import RpCard from './RpCard.vue'
import { formatRate } from '@/js/composables/roles/shared/profile/useRoleProfile'

defineProps<{
  commission: number
  /** Explica a qué aplica la comisión en este rol (null = no se muestra). */
  commissionHint?: string | null
  currencies: string[]
  games: string[]
}>()
</script>

<template>
  <RpCard title="Configuración" icon="fa-sliders" subtitle="Lo que tienes habilitado">
    <div v-if="commission > 0" class="rp-cfg-rate">
      <span class="rp-cfg-rate__value">{{ formatRate(commission) }}</span>
      <span class="rp-cfg-rate__text">
        <strong>Comisión</strong>
        <small v-if="commissionHint">{{ commissionHint }}</small>
      </span>
    </div>

    <div class="rp-cfg-groups">
      <div class="rp-cfg-group">
        <span class="rp-cfg-group__label">Monedas habilitadas</span>
        <div class="rp-cfg-group__tags">
          <span v-for="c in currencies" :key="c" class="rp-tag rp-tag--currency">{{ c }}</span>
          <span v-if="!currencies.length" class="rp-cfg-empty">Ninguna</span>
        </div>
      </div>
      <div class="rp-cfg-group">
        <span class="rp-cfg-group__label">Juegos habilitados</span>
        <div class="rp-cfg-group__tags">
          <span v-for="g in games" :key="g" class="rp-tag rp-tag--game">{{ g }}</span>
          <span v-if="!games.length" class="rp-cfg-empty">Ninguno</span>
        </div>
      </div>
    </div>

    <p class="rp-note"><i class="fas fa-lock"></i>La comisión, las monedas y los juegos los configura la administración.</p>
  </RpCard>
</template>

<style scoped>
.rp-cfg-rate {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.75rem 0.9rem; margin-bottom: 0.9rem;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.14), rgba(118, 75, 162, 0.12));
  border: 1px solid rgba(124, 140, 240, 0.35);
}
.rp-cfg-rate__value { font-size: 1.5rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-cfg-rate__text { display: flex; flex-direction: column; }
.rp-cfg-rate__text strong { font-size: 0.82rem; color: var(--ds-text); }
.rp-cfg-rate__text small { font-size: 0.74rem; color: var(--ds-text-2); }

.rp-cfg-groups { display: flex; flex-direction: column; gap: 0.7rem; }
.rp-cfg-group__label { display: block; margin-bottom: 0.3rem; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-cfg-group__tags { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.rp-tag { font-size: 0.72rem; font-weight: 700; padding: 3px 11px; border-radius: 999px; }
.rp-tag--currency { background: rgba(37, 99, 235, 0.14); color: #3b82f6; }
.rp-tag--game { background: rgba(124, 58, 237, 0.14); color: #8b5cf6; }
.rp-cfg-empty { font-size: 0.78rem; color: var(--ds-text-muted); }

.rp-note { display: flex; align-items: center; gap: 0.4rem; margin: 0.9rem 0 0; font-size: 0.74rem; color: var(--ds-text-muted); }
</style>
