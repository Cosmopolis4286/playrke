<script setup lang="ts" name="UserEditForm">
  import { computed } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdminUserEditForm } from '@/js/composables/roles/admin/user/useAdminUserEditForm'

  // Types
  import type { Country } from '@/js/types/country'
  import { ROLE_DETAIL_ROUTE_MAP, ROLES_WITH_AUTO_OPEN, type AdminRole, type AdminUserEditData } from '@/js/types/roles/admin/admin-user'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    modelValue: Partial<AdminUserEditData>
    countries: Country[]
    roles: AdminRole[]
    /** true si el usuario editado es superadmin. */
    isTargetSuperadmin: boolean
    /** true si el usuario editado es admin (otro admin, no el actor). */
    isTargetAdmin?: boolean
    /** true si el actor autenticado es superadmin. */
    isActorSuperadmin: boolean
    /** true si el usuario editado es el propio actor autenticado. */
    isSelf: boolean
    /** Nombre del rol del usuario editado en minúsculas (ej. 'commerce'), para decidir si mostrar "Ver más". */
    targetRoleName?: string
    /** Id del registro de dominio (Commerce/Seller/etc.) del usuario editado, para el botón "Ver más". */
    targetDomainId?: string | number | null
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<AdminUserEditData>): void
    (e: 'reset-password'): void
    (e: 'view-role-detail', route: string, domainId: string | number | null): void
  }>()

  /* ==========================================================================
   * "Ver más" — visible si el rol del usuario tiene página dedicada
   * (ROLE_DETAIL_ROUTE_MAP). El domainId solo es obligatorio para roles que
   * auto-abren una ficha (ROLES_WITH_AUTO_OPEN: commerce/customer/player/
   * seller/treasurer) — 'admin' navega a /admins sin abrir nada (esa página
   * no tiene modal de edición de datos), así que no debe bloquearse por falta
   * de domain_id (que para admin siempre es null, al no tener registro de
   * dominio propio).
   * ========================================================================== */

  const roleDetailRoute = computed(() =>
    props.targetRoleName ? ROLE_DETAIL_ROUTE_MAP[props.targetRoleName] ?? null : null
  )

  const canViewRoleDetail = computed(() => {
    if (!roleDetailRoute.value) return false
    if (!props.targetRoleName) return false
    return ROLES_WITH_AUTO_OPEN.has(props.targetRoleName) ? !!props.targetDomainId : true
  })

  function viewRoleDetail() {
    if (!canViewRoleDetail.value || !roleDetailRoute.value) return
    emit('view-role-detail', roleDetailRoute.value, props.targetDomainId ?? null)
  }

  /* ==========================================================================
   * Form logic
   * ========================================================================== */

  const { form, errors, isValid: isFormDataValid } = useAdminUserEditForm(() => props.modelValue, emit)

  /* ==========================================================================
   * Bloqueo de rol
   * ========================================================================== */

  const isRoleLocked = computed(() =>
    props.isSelf ||
    (props.isTargetSuperadmin && !props.isActorSuperadmin) ||
    (props.isTargetAdmin && !props.isActorSuperadmin)
  )

  const roleLockedReason = computed(() => {
    if (props.isSelf) return 'No puedes cambiar tu propio rol'
    if (props.isTargetSuperadmin && !props.isActorSuperadmin) return 'Solo un Super Administrador puede reasignar este rol'
    if (props.isTargetAdmin && !props.isActorSuperadmin) return 'Solo un Super Administrador puede modificar el rol de otro Administrador'
    return ''
  })

  /**
   * Opciones de rol disponibles en el selector — un actor que no es
   * superadmin nunca debe poder seleccionar "Admin" (evita promover a
   * alguien a su propio nivel), reforzado también en el backend vía
   * AdminUserService::guardRoleChange().
   */
  const selectableRoles = computed(() =>
    props.isActorSuperadmin
      ? props.roles
      : props.roles.filter(r => r.name.toLowerCase() !== 'admin')
  )

  /**
   * Bloquea el formulario COMPLETO (no solo el rol): un admin no puede
   * modificar ningún dato de otro admin — el backend rechaza la request
   * entera vía AdminUserService::guardMutation(), así que ni vale la pena
   * dejar editables nombre/username/email/país aquí. En el flujo normal la
   * fila ya está deshabilitada en la tabla (ver disabledRowIds en Index.vue)
   * y este modal no llega a abrirse; esto es defensa en profundidad.
   */
  const isFormLocked = computed(() => !!props.isTargetAdmin && !props.isActorSuperadmin)

  const isValid = computed(() => isFormDataValid.value && !isFormLocked.value)

  /* ==========================================================================
   * Submit
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form id="user-edit-form" novalidate @submit.prevent="submit">
    <div v-if="isSelf" class="alert alert-info small mb-3">
      <i class="fas fa-info-circle mr-1"></i>
      Estás editando tu propia cuenta — el rol no puede modificarse desde aquí.
    </div>
    <div v-else-if="isTargetSuperadmin && !isActorSuperadmin" class="alert alert-warning small mb-3">
      <i class="fas fa-shield-alt mr-1"></i>
      Esta cuenta es Super Administrador — solo otro Super Administrador puede cambiar su rol.
    </div>
    <div v-else-if="isTargetAdmin && !isActorSuperadmin" class="alert alert-warning small mb-3">
      <i class="fas fa-shield-alt mr-1"></i>
      Esta cuenta es Administrador — solo un Super Administrador puede modificarla.
    </div>

    <div class="row">
      <FormInput
        label="Nombre"
        name="first_name"
        required
        col="col-sm-6"
        :error="errors.first_name"
        :disabled="isFormLocked"
        v-model="form.first_name"
      />

      <FormInput
        label="Apellido"
        name="last_name"
        required
        col="col-sm-6"
        :error="errors.last_name"
        :disabled="isFormLocked"
        v-model="form.last_name"
      />

      <FormInput
        label="Username"
        name="username"
        required
        minlength="3"
        maxlength="20"
        col="col-sm-6"
        :error="errors.username"
        :disabled="isFormLocked"
        v-model="form.username"
      />

      <FormInput
        label="Email"
        name="email"
        type="email"
        required
        col="col-sm-6"
        :error="errors.email"
        :disabled="isFormLocked"
        v-model="form.email"
      />

      <FormInput
        label="Teléfono"
        name="phone_number"
        type="tel"
        col="col-sm-6"
        :error="errors.phone_number"
        :disabled="isFormLocked"
        v-model="form.phone_number"
      />

      <FormSelect
        label="País"
        name="country_id"
        required
        placeholder="Seleccionar país"
        :options="props.countries"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        :error="errors.country_id"
        :disabled="isFormLocked"
        v-model="form.country_id"
      />

      <div class="col-sm-6">
        <FormSelect
          label="Rol"
          name="role_id"
          required
          placeholder="Seleccionar rol"
          :options="selectableRoles"
          optionValue="id"
          optionLabel="name"
          col=""
          :disabled="isRoleLocked || isFormLocked"
          v-model="form.role_id"
        />
        <small v-if="isRoleLocked" class="text-muted d-block role-locked-hint">
          <i class="fas fa-lock mr-1"></i>{{ roleLockedReason }}
        </small>
      </div>
    </div>

    <div class="user-edit-form-actions mt-2">
      <button type="button" class="btn btn-outline-dark btn-sm" :disabled="isFormLocked" @click="emit('reset-password')">
        <i class="fas fa-key mr-2"></i>
        Resetear contraseña
      </button>

      <button
        v-if="canViewRoleDetail"
        type="button"
        class="btn btn-outline-primary btn-sm"
        :title="targetRoleName && ROLES_WITH_AUTO_OPEN.has(targetRoleName)
          ? 'Abre la página de gestión de este rol con la ficha del usuario ya cargada'
          : 'Abre la página de gestión de este rol'"
        @click="viewRoleDetail"
      >
        <i class="fas fa-arrow-up-right-from-square mr-2"></i>
        Ver más
      </button>
    </div>
  </form>
</template>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}

.btn-outline-dark {
  background: transparent;
  border: 1.5px solid var(--ds-border-2);
  color: var(--ds-text);
}
.btn-outline-dark:hover { background: var(--ds-surface-2); }

.btn-outline-primary {
  background: transparent;
  border: 1.5px solid #667eea;
  color: #667eea;
}
.btn-outline-primary:hover { background: rgba(102, 126, 234, 0.1); }

.user-edit-form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.role-locked-hint { margin-top: -0.5rem; font-size: 0.75rem; }
</style>
