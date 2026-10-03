<script setup lang="ts">
/**
 * Ficha de autogestión de las 3 entidades de negocio ("Mi Comercio", "Mi
 * Agencia", "Mi Perfil de Vendedor"). Una sola vista para los 3 roles — antes
 * eran 3 páginas copiadas casi línea por línea; las diferencias por rol
 * (red, deuda con la plataforma, cadena jerárquica, regla de comisión) las
 * resuelve cada sección según profile.role.
 *
 * Layout: hero + alertas a todo el ancho; columna principal (desempeño,
 * comisión/jerarquía, red, movimientos) y columna lateral (contacto,
 * billetera, seguridad). En móvil se apilan.
 */
import { computed } from 'vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import RoleProfileHero from './RoleProfileHero.vue'
import RoleProfileAlerts from './RoleProfileAlerts.vue'
import RoleProfilePerformance from './RoleProfilePerformance.vue'
import RoleProfileCommission from './RoleProfileCommission.vue'
import RoleProfileNetwork from './RoleProfileNetwork.vue'
import RoleProfileActivity from './RoleProfileActivity.vue'
import RoleProfileContact from './RoleProfileContact.vue'
import RoleProfileWallet from './RoleProfileWallet.vue'
import RoleProfileSecurity from './RoleProfileSecurity.vue'
import { ROLE_META, useRoleProfile } from '@/js/composables/roles/shared/profile/useRoleProfile'
import type { RoleProfile, RoleProfileRole } from '@/js/types/roles/shared/role-profile-common'

const props = defineProps<{
  profile: RoleProfile
  isAdminView?: boolean
}>()

const PAGE: Record<RoleProfileRole, { title: string; subtitle: string; adminList: string }> = {
  commerce:   { title: 'Mi Comercio', subtitle: 'Tu comercializadora: desempeño, red y configuración.', adminList: '/admin/commerces' },
  subsidiary: { title: 'Mi Agencia', subtitle: 'Tu agencia: desempeño, vendedores y configuración.', adminList: '/admin/subsidiaries' },
  seller:     { title: 'Mi Perfil de Vendedor', subtitle: 'Tu taquilla: ventas, comisión y configuración.', adminList: '/admin/sellers' },
}

/**
 * Presente cuando un admin/superadmin llegó desde "Ver perfil" en un listado.
 * Se propaga en el guardado — el backend re-valida el rol en cada request.
 */
const targetUserId = new URLSearchParams(window.location.search).get('target_user_id')

const editor = useRoleProfile(props.profile, targetUserId)
const profile = computed(() => editor.profile.value)
const page = computed(() => PAGE[profile.value.role])
</script>

<template>
  <PageHeader
    :icon="ROLE_META[profile.role].icon"
    :title="page.title"
    :subtitle="page.subtitle"
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: page.title },
    ]"
  />

  <section class="content">
    <div class="container-fluid rp-page">
      <div v-if="isAdminView" class="rp-admin-banner" role="status">
        <i class="fas fa-user-shield"></i>
        <span>
          Estás viendo la ficha de <strong>{{ profile.name }}</strong>
          <span class="rp-admin-banner__muted">(@{{ profile.user.username }})</span>. Toda acción aquí queda auditada.
        </span>
        <a :href="page.adminList" class="rp-admin-banner__exit"><i class="fas fa-arrow-left"></i>Volver al listado</a>
      </div>

      <RoleProfileHero :profile="profile" />
      <RoleProfileAlerts :alerts="profile.alerts" />

      <div class="rp-grid">
        <div class="rp-grid__main">
          <RoleProfilePerformance :performance="profile.performance" :role="profile.role" />
          <RoleProfileCommission :profile="profile" />
          <RoleProfileNetwork v-if="profile.role !== 'seller'" :profile="profile" />
          <RoleProfileActivity :movements="profile.recent_movements" />
        </div>
        <aside class="rp-grid__aside">
          <RoleProfileContact :editor="editor" />
          <RoleProfileWallet :wallets="profile.wallets" :receivables="profile.role === 'commerce' ? profile.receivables : undefined" />
          <RoleProfileSecurity :security="profile.security" :readonly="isAdminView" />
        </aside>
      </div>
    </div>
  </section>
</template>

<style scoped>
.rp-page { display: flex; flex-direction: column; gap: 1rem; padding-bottom: 1.5rem; }

.rp-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(300px, 360px);
  gap: 1rem;
  align-items: start;
}
.rp-grid__main, .rp-grid__aside { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
/* La columna lateral acompaña el scroll en pantallas altas: contacto y saldo siempre a mano. */
@media (min-width: 1200px) and (min-height: 820px) {
  .rp-grid__aside { position: sticky; top: 1rem; }
}
@media (max-width: 1199.98px) {
  .rp-grid { grid-template-columns: minmax(0, 1fr); }
  /* En una columna, contacto + billetera + seguridad arriba: es lo que más se consulta. */
  .rp-grid__aside { order: -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}

.rp-admin-banner {
  display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border: 1px solid rgba(217, 119, 6, 0.35);
  background: rgba(217, 119, 6, 0.1);
  font-size: 0.84rem; color: var(--ds-text);
}
.rp-admin-banner > i { color: #f59e0b; }
.rp-admin-banner__muted { color: var(--ds-text-muted); }
.rp-admin-banner__exit {
  margin-left: auto;
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 5px 12px; border-radius: 8px;
  font-size: 0.78rem; font-weight: 700;
  background: var(--ds-text); color: var(--ds-surface);
  text-decoration: none; white-space: nowrap;
}
.rp-admin-banner__exit:hover { opacity: 0.9; color: var(--ds-surface); text-decoration: none; }
</style>
