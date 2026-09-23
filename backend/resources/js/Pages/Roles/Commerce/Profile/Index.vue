<script setup lang="ts">
/**
 * Página de autogestión de la ficha propia del comercio — mismo patrón que
 * Roles/Subsidiary/Profile/Index.vue. Comisión/monedas/juegos quedan
 * siempre de solo lectura (no expuestos aquí en absoluto, se gestionan
 * desde admin/commerce).
 */
import { computed } from 'vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import FormInput from '@/js/Components/Common/Form/FormInput.vue'
import { useCommerceProfile } from '@/js/composables/roles/commerce/profile/useCommerceProfile'
import type { CommerceProfilePageProps } from '@/js/types/roles/commerce/profile/commerce-profile'

const props = defineProps<CommerceProfilePageProps>()

/**
 * Presente cuando un admin/superadmin llegó desde "Ver perfil" en el
 * listado de comercios (Roles/Admin/Commerce/Index.vue). Se propaga en
 * cada request del composable — el backend re-valida el rol en cada
 * llamada, esto es solo para no perderlo entre navegaciones internas.
 */
const targetUserId = new URLSearchParams(window.location.search).get('target_user_id')

const { commerce, editMode, saving, form, toggleEdit, save } = useCommerceProfile(props.commerce, targetUserId)

const isValid = computed(() => !!form.value.name.trim() && !!form.value.phone.trim())
</script>

<template>
  <PageHeader
    icon="fa-store"
    title="Mi Comercio"
    subtitle="Datos de tu comercializadora."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Mi Comercio' },
    ]"
  />

  <div v-if="props.isAdminView" class="alert alert-warning d-flex align-items-center profile-admin-view-banner" role="alert">
    <i class="fas fa-user-shield mr-2"></i>
    <span>
      Estás viendo la ficha de <strong>{{ commerce.name }}</strong>
      <span class="text-muted"> (@{{ commerce.user.username }})</span>.
      Toda acción aquí queda auditada.
    </span>
    <a href="/admin/commerce" class="btn btn-sm btn-dark ml-auto profile-admin-view-banner__exit">
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
                <i class="fas fa-store"></i>
              </div>
              <div class="sd-header-info">
                <h3 class="sd-name">{{ commerce.name }}</h3>
                <p class="sd-username">@{{ commerce.user.username }}</p>
              </div>
              <span class="badge sd-status" v-html="commerce.status_badge"></span>
            </div>

            <!-- Datos editables -->
            <div class="sd-section">
              <h4 class="sd-section-title"><i class="fas fa-address-card mr-2"></i>Datos de contacto</h4>

              <template v-if="!editMode">
                <div class="sd-grid">
                  <div class="sd-field">
                    <span class="sd-field-label"><i class="fas fa-signature"></i>Nombre</span>
                    <span class="sd-field-value">{{ commerce.name }}</span>
                  </div>
                  <div class="sd-field">
                    <span class="sd-field-label"><i class="fas fa-phone"></i>Teléfono</span>
                    <span class="sd-field-value">{{ commerce.phone }}</span>
                  </div>
                </div>
                <button type="button" class="sp-edit-btn" @click="toggleEdit">
                  <i class="fas fa-pen mr-1"></i>Editar
                </button>
              </template>

              <form v-else class="row" novalidate @submit.prevent="save">
                <FormInput label="Nombre" name="name" required col="col-sm-6" v-model="form.name" />
                <FormInput label="Teléfono" name="phone" required col="col-sm-6" v-model="form.phone" />

                <div class="col-12 sp-actions">
                  <button type="button" class="sp-cancel-btn" :disabled="saving" @click="toggleEdit">Cancelar</button>
                  <button type="submit" class="sp-save-btn" :disabled="saving || !isValid">
                    <i class="fas fa-spinner fa-spin mr-1" v-if="saving"></i>Guardar
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
                  <span class="sd-field-value">{{ commerce.country ?? '—' }}</span>
                </div>
                <div class="sd-field">
                  <span class="sd-field-label"><i class="fas fa-envelope"></i>Email</span>
                  <span class="sd-field-value">{{ commerce.user.email }}</span>
                </div>
              </div>
              <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle mr-1"></i>País, comisión, monedas y juegos son configurados por administración — para modificarlos, contacta a soporte.
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Reusa la misma paleta visual .sd- / .sp- de Roles/Subsidiary/Profile/Index.vue. */
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

.profile-admin-view-banner {
  flex-wrap: wrap;
  gap: 0.5rem;
}
.profile-admin-view-banner__exit {
  white-space: nowrap;
}
</style>
