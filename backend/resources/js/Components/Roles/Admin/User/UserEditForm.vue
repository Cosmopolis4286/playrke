<script setup lang="ts" name="UserEditForm">
  import { computed, ref, watch } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  // Composables
  import { useAdminUserEditForm } from '@/js/composables/roles/admin/user/useAdminUserEditForm'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'

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
    /** Status actual del usuario editado (ACTIVE/INACTIVE/...), para el switch de estado. */
    targetStatus?: string
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<AdminUserEditData>): void
    (e: 'reset-password'): void
    (e: 'view-role-detail', route: string, domainId: string | number | null): void
    (e: 'toggle-status'): void
  }>()

  /* ==========================================================================
   * Switch de estado (ACTIVE/INACTIVE) — el superadmin NUNCA puede
   * desactivarse desde aquí por seguridad (cuenta raíz de autorización, mismo
   * criterio que RolePolicy/AdminUserService en el resto del sistema). El
   * cambio real lo dispara el padre (Index.vue) vía el evento toggle-status,
   * reusando el mismo toggleStatus(id) + confirmación que ya usa la tabla —
   * este switch es solo la superficie visual, no muta el estado localmente.
   * ========================================================================== */

  const isStatusSwitchOn = computed(() => props.targetStatus === 'ACTIVE')
  const isStatusSwitchLocked = computed(() => props.isTargetSuperadmin || props.isSelf || (!!props.isTargetAdmin && !props.isActorSuperadmin))

  const statusSwitchLockedReason = computed(() => {
    if (props.isTargetSuperadmin) return 'La cuenta Super Administrador no puede desactivarse'
    if (props.isSelf) return 'No puedes cambiar el estado de tu propio usuario'
    if (props.isTargetAdmin && !props.isActorSuperadmin) return 'Solo un Super Administrador puede cambiar el estado de otro Administrador'
    return ''
  })

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
   * Teléfono — usePhoneField (validación compartida por código de país, ver
   * PhonePatterns.php/ValidPhoneForCountry.php en el backend para el espejo
   * de estas mismas reglas del lado servidor). A diferencia de los formularios
   * de rol (Commerce/Customer/Seller/Treasurer/Player), aquí el teléfono NO es
   * obligatorio — un admin puede dejarlo vacío sin bloquear el guardado.
   * ========================================================================== */

  const selectedIso2 = computed<string | null>(() => {
    if (!form.value.country_id) return null
    return props.countries.find(c => c.id === form.value.country_id)?.iso_code_2 ?? null
  })

  /** Ref local del teléfono — fuente de verdad para usePhoneField */
  const phoneRef = ref<string>(form.value.phone_number ?? '')

  watch(() => form.value.phone_number, v => {
    if (v !== phoneRef.value) phoneRef.value = v ?? ''
  })

  watch(phoneRef, v => {
    if (v !== form.value.phone_number) {
      form.value = { ...form.value, phone_number: v }
    }
  })

  const { phoneMask, phonePlaceholder, phoneHint, phoneError, isPhoneValid } =
    usePhoneField(selectedIso2, phoneRef, v => {
      phoneRef.value = v
    })

  /** Error visible: validación de formato primero, luego el que devuelva el backend. */
  const phoneErrorDisplay = computed(() => phoneError.value ?? errors.value.phone_number ?? null)

  /** Estado visual del campo teléfono — sin marcar inválido si está vacío (no es obligatorio). */
  const phoneValidState = computed<'valid' | 'invalid' | null>(() => {
    if (!phoneRef.value?.trim()) return null
    return isPhoneValid.value ? 'valid' : 'invalid'
  })

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

  /** El teléfono no es obligatorio, pero si el usuario escribió algo, debe cumplir el formato del país. */
  const isPhoneFieldValid = computed(() => !phoneRef.value?.trim() || isPhoneValid.value)

  const isValid = computed(() => isFormDataValid.value && isPhoneFieldValid.value && !isFormLocked.value)

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

      <FormInput
        label="Teléfono"
        name="phone_number"
        col="col-sm-6"
        :placeholder="phonePlaceholder"
        :mask="phoneMask"
        :maskEmitFormatted="true"
        :disabled="isFormLocked || !form.country_id"
        :inputValidState="phoneValidState"
        :error="phoneErrorDisplay"
        :hint="
          !phoneErrorDisplay && form.country_id
            ? phoneHint
            : !form.country_id
              ? 'Selecciona un país primero'
              : undefined
        "
        v-model="phoneRef"
      >
        <template #prepend>
          <span class="input-group-text px-2">
            <i class="fa-solid fa-phone fa-fw text-muted"></i>
          </span>
        </template>
      </FormInput>

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

      <div v-if="targetStatus" class="col-sm-6">
        <FormSwitch
          :label="isStatusSwitchOn ? 'Activo' : 'Inactivo'"
          name="status"
          col=""
          :model-value="isStatusSwitchOn"
          :disabled="isStatusSwitchLocked"
          @update:model-value="emit('toggle-status')"
        />
        <small v-if="isStatusSwitchLocked" class="text-muted d-block role-locked-hint">
          <i class="fas fa-lock mr-1"></i>{{ statusSwitchLockedReason }}
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
