<script setup lang="ts" name="UserEditForm">
  import { computed } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useAdminUserEditForm } from '@/js/composables/roles/admin/user/useAdminUserEditForm'

  // Types
  import type { Country } from '@/js/types/country'
  import type { AdminRole, AdminUserEditData } from '@/js/types/roles/admin/admin-user'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    modelValue: Partial<AdminUserEditData>
    countries: Country[]
    roles: AdminRole[]
    /** true si el usuario editado es superadmin. */
    isTargetSuperadmin: boolean
    /** true si el actor autenticado es superadmin. */
    isActorSuperadmin: boolean
    /** true si el usuario editado es el propio actor autenticado. */
    isSelf: boolean
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: Partial<AdminUserEditData>): void
    (e: 'reset-password'): void
  }>()

  /* ==========================================================================
   * Form logic
   * ========================================================================== */

  const { form, errors, isValid } = useAdminUserEditForm(() => props.modelValue, emit)

  /* ==========================================================================
   * Bloqueo de rol
   * ========================================================================== */

  const isRoleLocked = computed(() =>
    props.isSelf || (props.isTargetSuperadmin && !props.isActorSuperadmin)
  )

  const roleLockedReason = computed(() => {
    if (props.isSelf) return 'No puedes cambiar tu propio rol'
    if (props.isTargetSuperadmin && !props.isActorSuperadmin) return 'Solo un Super Administrador puede reasignar este rol'
    return ''
  })

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

    <div class="row">
      <FormInput
        label="Nombre"
        name="first_name"
        required
        col="col-sm-6"
        :error="errors.first_name"
        v-model="form.first_name"
      />

      <FormInput
        label="Apellido"
        name="last_name"
        required
        col="col-sm-6"
        :error="errors.last_name"
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
        v-model="form.username"
      />

      <FormInput
        label="Email"
        name="email"
        type="email"
        required
        col="col-sm-6"
        :error="errors.email"
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
        v-model="form.country_id"
      />

      <div class="col-sm-6">
        <FormSelect
          label="Rol"
          name="role_id"
          required
          placeholder="Seleccionar rol"
          :options="props.roles"
          optionValue="id"
          optionLabel="name"
          col=""
          :disabled="isRoleLocked"
          v-model="form.role_id"
        />
        <small v-if="isRoleLocked" class="text-muted d-block role-locked-hint">
          <i class="fas fa-lock mr-1"></i>{{ roleLockedReason }}
        </small>
      </div>
    </div>

    <button type="button" class="btn btn-outline-dark btn-sm mt-2" @click="emit('reset-password')">
      <i class="fas fa-key mr-2"></i>
      Resetear contraseña
    </button>
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

.role-locked-hint { margin-top: -0.5rem; font-size: 0.75rem; }
</style>
