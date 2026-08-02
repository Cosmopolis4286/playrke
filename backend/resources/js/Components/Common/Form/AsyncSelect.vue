<script setup lang="ts" name="AsyncSelect">
  import { computed } from 'vue'
  import Multiselect from '@vueform/multiselect'

  /**
   * Select simple (single) con búsqueda async server-side — a diferencia de
   * FormSelect (opciones estáticas ya cargadas), este componente recibe una
   * función `search` que resuelve las opciones bajo demanda según lo que
   * escribe el usuario (ej. selector de usuario/anfitrión).
   */
  const props = defineProps<{
    id?: string
    label: string
    name: string
    error?: string | null
    modelValue: string | number | null
    /** Función async que recibe la query y retorna las opciones {value, label}. */
    search: (query: string) => Promise<{ value: string | number; label: string }[]>
    /** Opción ya conocida a mostrar seleccionada (ej. al editar) sin tener que buscarla primero. */
    initialOption?: { value: string | number; label: string } | null
    required?: boolean
    disabled?: boolean
    placeholder?: string
    col?: string
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void
  }>()

  const inputId = computed(() => props.id ?? props.name)

  const value = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', (v as string | number | null) ?? null)
  })

  async function resolveOptions(query: string) {
    if (!query || query.trim().length < 2) {
      return props.initialOption ? [props.initialOption] : []
    }
    return props.search(query.trim())
  }
</script>

<template>
  <div :class="['form-group', col ?? 'col-sm-6']">
    <label :for="inputId" class="form-label fw-semibold">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <Multiselect
      v-model="value"
      :id="inputId"
      mode="single"
      :searchable="true"
      :filter-results="false"
      :resolve-on-load="false"
      :min-chars="2"
      :delay="350"
      :options="resolveOptions"
      value-prop="value"
      label="label"
      track-by="label"
      :placeholder="placeholder ?? 'Buscar usuario...'"
      :clearable="!required"
      :can-clear="!required"
      :disabled="disabled"
      append-to-body
      class="multiselect-bootstrap"
      :class="{ 'ms-invalid': !!error }"
      no-results-text="Sin resultados"
      no-options-text="Escribe para buscar..."
    />

    <small v-if="error" class="text-danger d-block mt-1">
      {{ error }}
    </small>
  </div>
</template>
