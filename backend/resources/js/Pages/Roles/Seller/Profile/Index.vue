<script setup lang="ts">
/**
 * Página de autogestión de la ficha propia del vendedor — mismo patrón que
 * Roles/Commerce/Profile/Index.vue y Roles/Subsidiary/Profile/Index.vue.
 * Comisión/manager/agencia quedan siempre de solo lectura.
 */
import { computed } from 'vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import FormInput from '@/js/Components/Common/Form/FormInput.vue'
import RoleProfileExtras from '@/js/Components/Roles/Shared/RoleProfileExtras.vue'
import { useSellerProfile } from '@/js/composables/roles/seller/profile/useSellerProfile'
import type { SellerProfilePageProps } from '@/js/types/roles/seller/profile/seller-profile'

const props = defineProps<SellerProfilePageProps>()

/**
 * Presente cuando un admin/superadmin llegó desde "Ver perfil" en el
 * listado de vendedores (Roles/Seller/Manager/Index.vue). Se propaga en
 * cada request del composable — el backend re-valida el rol en cada
 * llamada, esto es solo para no perderlo entre navegaciones internas.
 */
const targetUserId = new URLSearchParams(window.location.search).get('target_user_id')

const { seller, editMode, saving, justSaved, form, toggleEdit, save } = useSellerProfile(props.seller, targetUserId)

const isValid = computed(() => !!form.value.name.trim() && !!form.value.phone.trim())
</script>

<template>
  <PageHeader
    icon="fa-user-tie"
    title="Mi Perfil de Vendedor"
    subtitle="Datos de tu taquilla/perfil de vendedor."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Mi Perfil' },
    ]"
  />

  <div v-if="props.isAdminView" class="alert alert-warning d-flex align-items-center profile-admin-view-banner" role="alert">
    <i class="fas fa-user-shield mr-2"></i>
    <span>
      Estás viendo la ficha de <strong>{{ seller.name }}</strong>
      <span class="text-muted"> (@{{ seller.user.username }})</span>.
      Toda acción aquí queda auditada.
    </span>
    <a href="/sellers" class="btn btn-sm btn-dark ml-auto profile-admin-view-banner__exit">
      <i class="fas fa-arrow-left mr-1"></i>Volver al listado
    </a>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 col-lg-8">
          <div class="sp-card">
            <!-- Identidad -->
            <div class="sd-header">
              <div class="sd-avatar">
                <i class="fas fa-user-tie"></i>
              </div>
              <div class="sd-header-info">
                <h3 class="sd-name">{{ seller.name }}</h3>
                <p class="sd-username">@{{ seller.user.username }}</p>
              </div>
              <span class="badge sd-status" v-html="seller.status_badge"></span>
            </div>

            <!-- Jerarquía (solo lectura) -->
            <div class="sd-section">
              <h4 class="sd-section-title"><i class="fas fa-sitemap mr-2"></i>Reporto a</h4>
              <div class="sd-grid">
                <div class="sd-field">
                  <span class="sd-field-label">
                    <i :class="seller.reports_to.type === 'subsidiary' ? 'fas fa-code-branch' : 'fas fa-store'"></i>
                    {{ seller.reports_to.type === 'subsidiary' ? 'Agencia' : 'Comercio' }}
                  </span>
                  <span class="sd-field-value">{{ seller.reports_to.name }}</span>
                </div>
                <div class="sd-field" v-if="seller.reports_to.type === 'subsidiary' && seller.reports_to.commerce_name">
                  <span class="sd-field-label"><i class="fas fa-building"></i>Comercio</span>
                  <span class="sd-field-value">{{ seller.reports_to.commerce_name }}</span>
                </div>
              </div>
            </div>

            <!-- Datos editables -->
            <div class="sd-section">
              <div class="sd-section-title-row">
                <h4 class="sd-section-title"><i class="fas fa-address-card mr-2"></i>Datos de contacto</h4>
                <Transition name="rp-fade">
                  <span v-if="justSaved" class="rp-saved-badge">
                    <i class="fas fa-check-circle mr-1"></i>Guardado
                  </span>
                </Transition>
              </div>

              <template v-if="!editMode">
                <div class="sd-grid">
                  <div class="sd-field">
                    <span class="sd-field-label"><i class="fas fa-signature"></i>Nombre</span>
                    <span class="sd-field-value">{{ seller.name }}</span>
                  </div>
                  <div class="sd-field">
                    <span class="sd-field-label"><i class="fas fa-phone"></i>Teléfono</span>
                    <span class="sd-field-value">{{ seller.phone }}</span>
                  </div>
                </div>
                <button type="button" class="sp-edit-btn" @click="toggleEdit">
                  <i class="fas fa-pen mr-1"></i>Editar
                </button>
              </template>

              <form v-else class="row" novalidate @submit.prevent="save">
                <FormInput label="Nombre" name="name" required col="col-sm-6" :disabled="saving" v-model="form.name" />
                <FormInput label="Teléfono" name="phone" required col="col-sm-6" :disabled="saving" v-model="form.phone" />

                <div class="col-12 sp-actions">
                  <button type="button" class="sp-cancel-btn" :disabled="saving" @click="toggleEdit">Cancelar</button>
                  <button type="submit" class="sp-save-btn" :disabled="saving || !isValid">
                    <i class="fas fa-spinner fa-spin mr-1" v-if="saving"></i>{{ saving ? 'Guardando...' : 'Guardar' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Solo lectura -->
            <div class="sd-section">
              <h4 class="sd-section-title"><i class="fas fa-globe mr-2"></i>Configuración</h4>
              <div class="sd-grid">
                <div class="sd-field">
                  <span class="sd-field-label"><i class="fas fa-flag"></i>País</span>
                  <span class="sd-field-value">{{ seller.country ?? '—' }}</span>
                </div>
                <div class="sd-field">
                  <span class="sd-field-label"><i class="fas fa-envelope"></i>Email</span>
                  <span class="sd-field-value">{{ seller.user.email }}</span>
                </div>
              </div>
              <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle mr-1"></i>Comisión y a quién reportas son configurados por tu comercio/agencia — para modificarlos, contáctalos.
              </small>
            </div>

            <RoleProfileExtras
              :commission="seller.commission"
              :currencies="seller.currencies"
              :games="seller.games"
              :wallets="seller.wallets"
              :recent-movements="seller.recent_movements"
              :account-meta="seller.account_meta"
            />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Reusa la misma paleta visual .sd- / .sp- de las páginas de perfil hermanas. */
.sp-card { background: var(--ds-surface); border: 1px solid var(--ds-border); border-radius: 12px; padding: 1.25rem 1.5rem; }

.sd-header { display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--ds-border); }
.sd-avatar { width: 44px; height: 44px; flex-shrink: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; background: linear-gradient(135deg, #667eea, #764ba2); }
.sd-header-info { flex: 1; min-width: 0; }
.sd-name { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--ds-text); }
.sd-username { font-size: 0.82rem; color: var(--ds-text-muted); margin: 0.15rem 0 0; }
.sd-status { font-size: 0.7rem; }

.sd-section { margin-bottom: 1.25rem; }
.sd-section:last-child { margin-bottom: 0; }
.sd-section-title { display: flex; align-items: center; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--ds-text-muted); margin: 0 0 0.6rem; }
.sd-section-title i { opacity: 0.7; }
.sd-section-title-row { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
.sd-section-title-row .sd-section-title { margin: 0 0 0.6rem; }

.sd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
.sd-field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.sd-field-label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.sd-field-label i { font-size: 0.68rem; opacity: 0.75; }
.sd-field-value { font-size: 0.9rem; color: var(--ds-text); font-weight: 600; }

.sp-edit-btn {
  margin-top: 0.85rem;
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 600;
  cursor: pointer; border: 1.5px solid var(--ds-border-2); background: transparent; color: var(--ds-text-2);
  transition: all 0.15s;
}
.sp-edit-btn:hover { background: var(--ds-surface-2); }

.sp-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem; }
.sp-cancel-btn, .sp-save-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 18px; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; line-height: 1.4; transition: all 0.15s;
}
.sp-cancel-btn { border: 1.5px solid var(--ds-border-2); background: transparent; color: var(--ds-text-2); }
.sp-cancel-btn:hover { background: var(--ds-surface-2); }
.sp-save-btn { border: none; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.sp-save-btn:hover { filter: brightness(1.08); }
.sp-save-btn:disabled, .sp-cancel-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.rp-saved-badge {
  display: inline-flex; align-items: center;
  font-size: 0.75rem; font-weight: 600;
  color: #4ade80;
}
.rp-fade-enter-active, .rp-fade-leave-active { transition: opacity 0.3s; }
.rp-fade-enter-from, .rp-fade-leave-to { opacity: 0; }

.profile-admin-view-banner {
  flex-wrap: wrap;
  gap: 0.5rem;
}
.profile-admin-view-banner__exit {
  white-space: nowrap;
}
</style>
