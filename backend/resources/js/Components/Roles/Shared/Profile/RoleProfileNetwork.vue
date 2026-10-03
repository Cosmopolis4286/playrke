<script setup lang="ts">
import { computed } from 'vue'
import RpCard from './RpCard.vue'
import type { CommerceProfile, RoleProfileNetworkRow, SubsidiaryProfile } from '@/js/types/roles/shared/role-profile-common'
import { formatInteger, formatRate, statusMeta } from '@/js/composables/roles/shared/profile/useRoleProfile'

const props = defineProps<{ profile: CommerceProfile | SubsidiaryProfile }>()

const isCommerce = computed(() => props.profile.role === 'commerce')

const stats = computed(() => {
  if (props.profile.role === 'commerce') {
    const n = props.profile.network
    return [
      { icon: 'fa-code-branch', label: 'Agencias', value: n.subsidiaries_count },
      { icon: 'fa-user-tie', label: 'Vendedores', value: n.sellers_count },
      { icon: 'fa-user-check', label: 'Directos', value: n.direct_sellers_count },
    ]
  }
  return [{ icon: 'fa-user-tie', label: 'Vendedores', value: props.profile.network.sellers_count }]
})

const rows = computed<RoleProfileNetworkRow[]>(() =>
  props.profile.role === 'commerce' ? props.profile.network.subsidiaries : props.profile.network.sellers,
)

const topSellers = computed(() => (props.profile.role === 'commerce' ? props.profile.network.top_sellers : []))
const maxTop = computed(() => Math.max(1, ...topSellers.value.map((s) => s.cards)))

const listTitle = computed(() => (isCommerce.value ? 'Agencias' : 'Mis vendedores'))
const totalInList = computed(() => (props.profile.role === 'commerce' ? props.profile.network.subsidiaries_count : props.profile.network.sellers_count))
</script>

<template>
  <RpCard title="Mi red" icon="fa-sitemap" subtitle="Tu estructura comercial y su actividad este mes">
    <template #actions>
      <a href="/sellers" class="rp-link">Gestionar <i class="fas fa-arrow-right"></i></a>
    </template>

    <div class="rp-net-stats">
      <div v-for="s in stats" :key="s.label" class="rp-net-stat">
        <i :class="['fas', s.icon]"></i>
        <span class="rp-net-stat__value">{{ formatInteger(s.value) }}</span>
        <span class="rp-net-stat__label">{{ s.label }}</span>
      </div>
    </div>

    <template v-if="rows.length">
      <div class="rp-net-list-head">
        <span>{{ listTitle }}</span>
        <a v-if="isCommerce" href="/admin/subsidiaries" class="rp-link rp-link--small">Ver agencias</a>
      </div>
      <ul class="rp-net-list">
        <li v-for="row in rows" :key="row.name" class="rp-net-row">
          <span :class="['rp-net-row__dot', `rp-net-row__dot--${statusMeta(row.status).tone}`]" :title="statusMeta(row.status).label"></span>
          <div class="rp-net-row__main">
            <span class="rp-net-row__name">{{ row.name }}</span>
            <span class="rp-net-row__meta">
              {{ row.commission > 0 ? formatRate(row.commission) : 'Sin comisión' }}
              <template v-if="row.sellers_count !== undefined"> · {{ row.sellers_count }} vendedor(es)</template>
            </span>
          </div>
          <span class="rp-net-row__metric">
            <strong>{{ formatInteger(row.month_cards) }}</strong>
            <small>cartones</small>
          </span>
        </li>
      </ul>
      <p v-if="totalInList > rows.length" class="rp-net-more">Mostrando {{ rows.length }} de {{ totalInList }}.</p>
    </template>
    <p v-else class="rp-net-empty">
      <i class="fas fa-user-plus"></i>
      {{ isCommerce ? 'Aún no tienes agencias.' : 'Aún no tienes vendedores.' }}
    </p>

    <div v-if="topSellers.length" class="rp-top">
      <span class="rp-top__title"><i class="fas fa-trophy"></i>Mejores vendedores del mes</span>
      <ol class="rp-top__list">
        <li v-for="(s, i) in topSellers" :key="s.name" class="rp-top__row">
          <span class="rp-top__pos">{{ i + 1 }}</span>
          <div class="rp-top__main">
            <div class="rp-top__line">
              <span class="rp-top__name">{{ s.name }}</span>
              <span class="rp-top__cards">{{ formatInteger(s.cards) }}</span>
            </div>
            <div class="rp-top__bar"><span :style="{ width: `${(s.cards / maxTop) * 100}%` }"></span></div>
            <span v-if="s.subsidiary" class="rp-top__sub">{{ s.subsidiary }}</span>
          </div>
        </li>
      </ol>
    </div>
  </RpCard>
</template>

<style scoped>
.rp-link {
  display: inline-flex; align-items: center; gap: 0.35rem;
  font-size: 0.78rem; font-weight: 700; color: #7c8cf0; text-decoration: none;
}
.rp-link:hover { text-decoration: underline; color: #7c8cf0; }
.rp-link--small { font-size: 0.72rem; }

.rp-net-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 0.6rem; }
.rp-net-stat {
  display: flex; flex-direction: column; align-items: flex-start; gap: 0.1rem;
  padding: 0.7rem 0.85rem; border-radius: 12px; background: var(--ds-surface-2);
}
.rp-net-stat i { font-size: 0.8rem; color: #7c8cf0; margin-bottom: 0.2rem; }
.rp-net-stat__value { font-size: 1.3rem; font-weight: 800; color: var(--ds-text); line-height: 1.1; }
.rp-net-stat__label { font-size: 0.72rem; color: var(--ds-text-muted); }

.rp-net-list-head {
  display: flex; align-items: center; justify-content: space-between;
  margin: 1rem 0 0.4rem;
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted);
}
.rp-net-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.rp-net-row {
  display: flex; align-items: center; gap: 0.7rem;
  padding: 0.55rem 0.25rem;
  border-bottom: 1px solid var(--ds-border);
}
.rp-net-row:last-child { border-bottom: none; }
.rp-net-row__dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rp-net-row__dot--success { background: #22c55e; }
.rp-net-row__dot--muted   { background: var(--ds-text-muted); }
.rp-net-row__dot--danger  { background: #f43f5e; }
.rp-net-row__dot--warning { background: #f59e0b; }
.rp-net-row__main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.rp-net-row__name { font-size: 0.86rem; font-weight: 700; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-net-row__meta { font-size: 0.72rem; color: var(--ds-text-muted); }
.rp-net-row__metric { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.1; }
.rp-net-row__metric strong { font-size: 0.95rem; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-net-row__metric small { font-size: 0.66rem; color: var(--ds-text-muted); }
.rp-net-more { margin: 0.4rem 0 0; font-size: 0.72rem; color: var(--ds-text-muted); }
.rp-net-empty {
  display: flex; align-items: center; gap: 0.5rem;
  margin: 0.9rem 0 0; padding: 0.7rem 0.85rem;
  border-radius: 10px; background: var(--ds-surface-2);
  font-size: 0.8rem; color: var(--ds-text-2);
}

.rp-top { margin-top: 1rem; padding-top: 0.9rem; border-top: 1px dashed var(--ds-border); }
.rp-top__title {
  display: inline-flex; align-items: center; gap: 0.4rem;
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted);
}
.rp-top__title i { color: #f59e0b; }
.rp-top__list { list-style: none; margin: 0.5rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.55rem; }
.rp-top__row { display: flex; align-items: flex-start; gap: 0.6rem; }
.rp-top__pos {
  width: 22px; height: 22px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%; font-size: 0.7rem; font-weight: 800;
  background: var(--ds-surface-2); color: var(--ds-text-2);
}
.rp-top__row:first-child .rp-top__pos { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.rp-top__main { flex: 1; min-width: 0; }
.rp-top__line { display: flex; justify-content: space-between; gap: 0.5rem; }
.rp-top__name { font-size: 0.82rem; font-weight: 700; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-top__cards { font-size: 0.8rem; font-weight: 700; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-top__bar { height: 5px; margin-top: 0.25rem; border-radius: 999px; background: var(--ds-surface-2); overflow: hidden; }
.rp-top__bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #667eea, #764ba2); }
.rp-top__sub { font-size: 0.68rem; color: var(--ds-text-muted); }
</style>
