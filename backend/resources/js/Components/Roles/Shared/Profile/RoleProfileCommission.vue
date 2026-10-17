<script setup lang="ts">
import { computed } from 'vue'
import RpCard from './RpCard.vue'
import type { AccountRole, RoleProfile, RoleProfileRole } from '@/js/types/roles/shared/role-profile-common'
import { ROLE_META, formatRate, statusMeta } from '@/js/composables/roles/shared/profile/useRoleProfile'

const props = defineProps<{ profile: RoleProfile }>()

const LEVEL_LABEL: Record<RoleProfileRole, string> = {
  commerce: 'Comercializadora',
  subsidiary: 'Agencia',
  seller: 'Taquilla',
}

const chain = computed(() => props.profile.hierarchy.chain)

const pct = (n: number) => formatRate(Math.max(0, Math.round(n * 100) / 100))

/**
 * Regla de cobro en lenguaje claro — reparto en cascada: cada nivel cobra la
 * diferencia con el nivel inferior (CommissionCalculationService::networkSplit()).
 */
const rule = computed<string[]>(() => {
  const p = props.profile
  const own = p.commission

  if (p.role === 'seller') {
    const lines = [`Cobras el ${pct(own)} de cada venta que registras.`]
    if (p.hierarchy.cap !== null) {
      // Solo llega cap cuando quien mira es admin (con los % de arriba visibles).
      const parent = chain.value.find((n) => n.level === 'subsidiary' && (n.rate ?? 0) > 0) ? 'tu agencia' : 'tu comercializadora'
      lines.push(`Tu comisión puede llegar hasta el ${pct(p.hierarchy.cap)}, el tope que define ${parent}.`)
    }
    return lines
  }

  if (p.role === 'subsidiary') {
    if (p.hierarchy.pass_through) {
      return [
        'Tu agencia es "de paso": no cobra margen propio.',
        'La diferencia entre la comisión de cada vendedor y la de tu comercializadora la cobra la comercializadora.',
      ]
    }
    const range = p.hierarchy.children_rate_range
    const lines = [`Cobras tu ${pct(own)} menos la comisión de cada vendedor, en cada venta que hagan.`]
    if (range) {
      lines.push(range.min === range.max
        ? `Con tus vendedores al ${pct(range.max)}, tu margen es ${pct(own - range.max)} por venta.`
        : `Con tus vendedores entre ${pct(range.min)} y ${pct(range.max)}, tu margen va de ${pct(own - range.max)} a ${pct(own - range.min)} por venta.`)
    }
    return lines
  }

  const range = p.hierarchy.children_rate_range
  const lines = [`Cobras tu ${pct(own)} menos la comisión de la agencia o del vendedor directo, en cada venta de tu red.`]
  if (range) {
    lines.push(range.min === range.max
      ? `Con tu red al ${pct(range.max)}, tu margen es ${pct(own - range.max)} por venta.`
      : `Con tu red entre ${pct(range.min)} y ${pct(range.max)}, tu margen va de ${pct(own - range.max)} a ${pct(own - range.min)} por venta.`)
  }
  return lines
})

/**
 * Ejemplo con números — SOLO con datos del propio nivel y de los de abajo
 * (regla: nadie ve la comisión de quien está por encima; el backend ni
 * siquiera la envía al dueño del perfil, ver SellerProfileService).
 *
 * - Vendedor: cuánto gana según cuánto venda (100 / 500 / 1.000).
 * - Agencia / comercializadora: por cada 100 que venda su red, cuánto se
 *   llevan los de abajo y cuánto gana ella (rango si sus comisiones varían).
 */
interface ExampleRow { label: string; value: string; self: boolean }

const num = (n: number) => Math.max(0, n).toLocaleString('es-VE', { maximumFractionDigits: 2 })
const span = (min: number, max: number) => (Math.abs(max - min) < 0.005 ? num(max) : `${num(min)} – ${num(max)}`)

const example = computed<{ title: string; rows: ExampleRow[] } | null>(() => {
  const p = props.profile
  const own = p.commission

  if (p.role === 'seller') {
    return {
      title: 'Lo que ganas según lo que vendas',
      rows: [100, 500, 1000].map((sold) => ({
        label: `Si vendes ${num(sold)}`,
        value: num((sold * own) / 100),
        self: sold === 100,
      })),
    }
  }

  if (p.role === 'subsidiary' && p.hierarchy.pass_through) return null

  const range = p.hierarchy.children_rate_range
  if (!range) return null

  return {
    title: 'Por cada 100 que venda tu red',
    rows: [
      { label: p.role === 'subsidiary' ? 'Tus vendedores cobran' : 'Tu red cobra', value: span(range.min, range.max), self: false },
      { label: 'Tú ganas', value: span(own - range.max, own - range.min), self: true },
    ],
  }
})

/** Celda de % de un nivel: oculto (nivel superior), "De paso" o el porcentaje. */
function rateLabel(node: { level: AccountRole; rate: number | null }): string | null {
  if (node.rate === null) return null
  return node.level === 'subsidiary' && node.rate <= 0 ? 'De paso' : formatRate(node.rate)
}

const currencies = computed(() => ('currencies' in props.profile ? props.profile.currencies : []))
const games = computed(() => ('games' in props.profile ? props.profile.games : []))
</script>

<template>
  <RpCard title="Comisión y jerarquía" icon="fa-percent" subtitle="Cómo se reparte cada venta en tu red">
    <ol class="rp-chain">
      <li v-for="node in chain" :key="node.level" :class="['rp-chain__node', { 'rp-chain__node--self': node.is_self }]">
        <span class="rp-chain__icon"><i :class="['fas', ROLE_META[node.level].icon]"></i></span>
        <div class="rp-chain__info">
          <span class="rp-chain__level">{{ LEVEL_LABEL[node.level] }}<template v-if="node.is_self"> · Tú</template></span>
          <span class="rp-chain__name">{{ node.name }}</span>
        </div>
        <span :class="['rp-chain__status', `rp-chain__status--${statusMeta(node.status).tone}`]" :title="statusMeta(node.status).label"></span>
        <span v-if="rateLabel(node) !== null" class="rp-chain__rate">{{ rateLabel(node) }}</span>
        <span v-else class="rp-chain__rate rp-chain__rate--hidden" title="Solo ese nivel ve su comisión">
          <i class="fas fa-lock"></i>
        </span>
      </li>
    </ol>

    <div class="rp-rule">
      <i class="fas fa-circle-info"></i>
      <div>
        <p v-for="line in rule" :key="line">{{ line }}</p>
      </div>
    </div>

    <div v-if="example" class="rp-example">
      <span class="rp-example__title">{{ example.title }}</span>
      <div class="rp-example__rows">
        <div v-for="row in example.rows" :key="row.label" :class="['rp-example__row', { 'rp-example__row--self': row.self }]">
          <span class="rp-example__label">{{ row.label }}</span>
          <span class="rp-example__amount">{{ row.value }}</span>
        </div>
      </div>
    </div>

    <div v-if="currencies.length || games.length" class="rp-enabled">
      <div v-if="currencies.length" class="rp-enabled__group">
        <span class="rp-enabled__label">Monedas habilitadas</span>
        <span v-for="c in currencies" :key="c" class="rp-tag rp-tag--currency">{{ c }}</span>
      </div>
      <div v-if="games.length" class="rp-enabled__group">
        <span class="rp-enabled__label">Juegos habilitados</span>
        <span v-for="g in games" :key="g" class="rp-tag rp-tag--game">{{ g }}</span>
      </div>
    </div>

    <p class="rp-note">
      <i class="fas fa-lock"></i>
      La comisión, las monedas y los juegos los configura tu nivel superior o la administración.
    </p>
  </RpCard>
</template>

<style scoped>
.rp-chain { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0; }
.rp-chain__node {
  position: relative;
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 12px;
}
.rp-chain__node + .rp-chain__node { margin-top: 0.55rem; }
/* conector vertical entre niveles */
.rp-chain__node + .rp-chain__node::before {
  content: '';
  position: absolute; left: calc(0.75rem + 15px); top: -0.6rem;
  width: 2px; height: 0.65rem;
  background: var(--ds-border-2);
}
.rp-chain__node--self {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.14), rgba(118, 75, 162, 0.12));
  border: 1px solid rgba(124, 140, 240, 0.35);
}
.rp-chain__icon {
  width: 32px; height: 32px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px;
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
  font-size: 0.8rem;
}
.rp-chain__node--self .rp-chain__icon { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.rp-chain__info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.rp-chain__level { font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-chain__name { font-size: 0.88rem; font-weight: 700; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-chain__status { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rp-chain__status--success { background: #22c55e; }
.rp-chain__status--muted   { background: var(--ds-text-muted); }
.rp-chain__status--danger  { background: #f43f5e; }
.rp-chain__status--warning { background: #f59e0b; }
.rp-chain__rate {
  min-width: 58px; text-align: right;
  font-size: 1rem; font-weight: 800; color: var(--ds-text);
  font-variant-numeric: tabular-nums;
}
.rp-chain__rate--hidden { font-size: 0.8rem; color: var(--ds-text-muted); cursor: help; }

.rp-rule {
  display: flex; gap: 0.6rem;
  margin-top: 0.9rem; padding: 0.75rem 0.85rem;
  border-radius: 10px;
  background: var(--ds-surface-2);
}
.rp-rule > i { color: #7c8cf0; margin-top: 0.15rem; }
.rp-rule p { margin: 0; font-size: 0.8rem; line-height: 1.45; color: var(--ds-text-2); }
.rp-rule p + p { margin-top: 0.25rem; }

.rp-example { margin-top: 0.75rem; }
.rp-example__title { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-example__rows { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.4rem; }
.rp-example__row {
  flex: 1 1 110px;
  display: flex; flex-direction: column;
  padding: 0.5rem 0.7rem;
  border-radius: 10px;
  border: 1px dashed var(--ds-border-2);
}
.rp-example__row--self { border-style: solid; border-color: rgba(124, 140, 240, 0.5); }
.rp-example__label { font-size: 0.72rem; color: var(--ds-text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-example__amount { font-size: 1.05rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; }

.rp-enabled { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.9rem; }
.rp-enabled__group { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
.rp-enabled__label { font-size: 0.74rem; color: var(--ds-text-muted); margin-right: 0.25rem; }
.rp-tag { font-size: 0.7rem; font-weight: 700; padding: 2px 10px; border-radius: 999px; }
.rp-tag--currency { background: rgba(37, 99, 235, 0.14); color: #3b82f6; }
.rp-tag--game { background: rgba(124, 58, 237, 0.14); color: #8b5cf6; }

.rp-note { display: flex; align-items: center; gap: 0.4rem; margin: 0.9rem 0 0; font-size: 0.74rem; color: var(--ds-text-muted); }
</style>
