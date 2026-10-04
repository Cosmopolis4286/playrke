<script setup lang="ts">
import { computed } from 'vue'
import type { RoleProfile } from '@/js/types/roles/shared/role-profile-common'
import { ROLE_META, formatDate, formatRate, statusMeta, timeAgo } from '@/js/composables/roles/shared/profile/useRoleProfile'

const props = defineProps<{ profile: RoleProfile }>()

const role = computed(() => ROLE_META[props.profile.role])
const status = computed(() => statusMeta(props.profile.status))

const initials = computed(() =>
  props.profile.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase())
    .join('') || '?',
)

/** Cadena hacia arriba (sin el propio nodo): "Comercio Demo › Sucursal Demo". */
const parents = computed(() => props.profile.hierarchy.chain.filter((n) => !n.is_self))
</script>

<template>
  <section class="rp-hero">
    <div class="rp-hero__banner" aria-hidden="true"></div>

    <div class="rp-hero__content">
      <div class="rp-hero__avatar">
        <span class="rp-hero__initials">{{ initials }}</span>
        <span class="rp-hero__role-icon" :title="role.label"><i :class="['fas', role.icon]"></i></span>
      </div>

      <div class="rp-hero__identity">
        <div class="rp-hero__chips">
          <span class="rp-chip rp-chip--role"><i :class="['fas', role.icon]"></i>{{ role.label }}</span>
          <span :class="['rp-chip', `rp-chip--${status.tone}`]"><span class="rp-dot"></span>{{ status.label }}</span>
        </div>
        <h1 class="rp-hero__name">{{ profile.name }}</h1>
        <p class="rp-hero__username">@{{ profile.user.username }}</p>

        <p v-if="parents.length" class="rp-hero__chain">
          <i class="fas fa-sitemap"></i>
          <template v-for="(node, i) in parents" :key="node.level">
            <span v-if="i > 0" class="rp-hero__sep">›</span>
            <span>{{ node.name }}</span>
          </template>
        </p>
      </div>

      <dl class="rp-hero__facts">
        <div class="rp-hero__fact">
          <dt>Comisión</dt>
          <dd>{{ formatRate(profile.commission) }}</dd>
        </div>
        <div class="rp-hero__fact">
          <dt>Miembro desde</dt>
          <dd>{{ formatDate(profile.account_meta.member_since) }}</dd>
        </div>
        <div class="rp-hero__fact">
          <dt>Última conexión</dt>
          <dd>{{ profile.account_meta.last_login_at ? timeAgo(profile.account_meta.last_login_at) : 'Nunca' }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<style scoped>
.rp-hero {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface);
}
.rp-hero__banner {
  height: 86px;
  background:
    radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.18), transparent 45%),
    linear-gradient(120deg, #667eea 0%, #764ba2 60%, #9f7aea 100%);
}
.rp-hero__content {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: end;
  gap: 1rem 1.25rem;
  padding: 0 1.5rem 1.25rem;
  margin-top: -38px;
}
.rp-hero__avatar { position: relative; justify-self: start; width: fit-content; }
.rp-hero__initials {
  width: 84px; height: 84px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 22px;
  font-size: 1.85rem; font-weight: 800; letter-spacing: 0.02em;
  color: #fff;
  background: linear-gradient(135deg, #5a67d8, #6b46c1);
  border: 4px solid var(--ds-surface);
  box-shadow: 0 8px 24px rgba(76, 81, 191, 0.35);
}
.rp-hero__role-icon {
  position: absolute; right: -6px; bottom: -4px;
  width: 30px; height: 30px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
  font-size: 0.75rem;
  background: var(--ds-surface);
  color: #7c8cf0;
  border: 2px solid var(--ds-border);
}
.rp-hero__identity { min-width: 0; padding-top: 44px; }
.rp-hero__chips { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.35rem; }
.rp-chip {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 2px 10px; border-radius: 999px;
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.02em;
}
.rp-chip i { font-size: 0.65rem; }
.rp-chip--role    { background: rgba(102, 126, 234, 0.14); color: #7c8cf0; }
.rp-chip--success { background: rgba(22, 163, 74, 0.15); color: #22c55e; }
.rp-chip--muted   { background: var(--ds-surface-2); color: var(--ds-text-2); }
.rp-chip--danger  { background: rgba(225, 29, 72, 0.15); color: #f43f5e; }
.rp-chip--warning { background: rgba(217, 119, 6, 0.16); color: #f59e0b; }
.rp-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.rp-hero__name {
  margin: 0;
  font-size: 1.5rem; font-weight: 800; line-height: 1.2;
  color: var(--ds-text);
  overflow-wrap: anywhere;
}
.rp-hero__username { margin: 0.1rem 0 0; font-size: 0.85rem; color: var(--ds-text-muted); }
.rp-hero__chain {
  display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem;
  margin: 0.45rem 0 0;
  font-size: 0.8rem; color: var(--ds-text-2);
}
.rp-hero__chain i { color: var(--ds-text-muted); font-size: 0.72rem; }
.rp-hero__sep { color: var(--ds-text-muted); }

.rp-hero__facts {
  display: flex; gap: 0.6rem; margin: 0;
}
.rp-hero__fact {
  min-width: 112px;
  padding: 0.55rem 0.8rem;
  border-radius: 12px;
  background: var(--ds-surface-2);
}
.rp-hero__fact dt {
  font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  color: var(--ds-text-muted);
}
.rp-hero__fact dd { margin: 0.15rem 0 0; font-size: 0.92rem; font-weight: 700; color: var(--ds-text); white-space: nowrap; }

@media (max-width: 991.98px) {
  .rp-hero__content { grid-template-columns: auto 1fr; }
  .rp-hero__facts { grid-column: 1 / -1; flex-wrap: wrap; }
  .rp-hero__fact { flex: 1 1 30%; }
}
@media (max-width: 575.98px) {
  .rp-hero__content { grid-template-columns: 1fr; padding: 0 1rem 1rem; }
  .rp-hero__identity { padding-top: 0; }
  .rp-hero__initials { width: 72px; height: 72px; font-size: 1.5rem; }
  .rp-hero__name { font-size: 1.25rem; }
}
</style>
