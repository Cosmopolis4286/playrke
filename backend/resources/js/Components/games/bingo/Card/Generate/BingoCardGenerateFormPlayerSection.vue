<script setup lang="ts" name="BingoCardGenerateFormPlayerSection">
  import { computed, ref, watch } from 'vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import backendClient from '@/js/plugins/axios'
  import { usePhoneField } from '@/js/composables/form/usePhoneField'
  import { showToast } from '@/js/utils/toastNotify'

  import type { Country } from '@/js/types/country'
  import type { Player, Room } from '@/js/types/games/bingo/card/bingo-card-generate'

  /* ───────── Props ───────── */

  const props = defineProps<{
    selectedRoom: Room | null
    countries: Country[]
    loadingCountries: boolean
    selectedCountry: string | null
    player: Player
  }>()

  /* ───────── Emits ───────── */

  const emit = defineEmits<{
    (e: 'update:selectedCountry', value: string | null): void
    (e: 'update:player', value: Player): void
    (e: 'update:playerExists', value: boolean): void
    (e: 'update:receiptData', value: { receiptNumber: string; receiptImage: File | null }): void
  }>()

  /* ───────── State ───────── */

  const checkingPlayer = ref(false)
  const playerExists = ref(false)
  const imagePreview = ref<string | null>(null)
  const uploadingProof = ref(false)
  const receiptNumber = ref('')
  const validationAttempted = ref(false)
  const selectedReceiptFile = ref<File | null>(null)
  const phoneChecked = ref(false)

  // Show proof of payment when all player fields have data and phone has been checked
  const showProofOfPayment = computed(() => {
    return !!(
      props.player.firstName &&
      props.player.lastName &&
      props.player.phone &&
      props.selectedCountry &&
      phoneChecked.value
    )
  })

  // Show name fields only after phone check
  const showNameFields = computed(() => {
    return phoneChecked.value
  })

  // Show popover ONLY when phone has value AND is valid AND not checked AND not empty
  const shouldShowPhonePopover = computed(() => {
    return !!props.player.phone && 
           props.player.phone.trim() !== '' && 
           isPhoneValid.value && 
           !phoneChecked.value && 
           !checkingPlayer.value
  })

  // Real-time validation
  const imageError = computed(() => {
    return showProofOfPayment.value && !imagePreview.value ? 'La imagen es requerida' : null
  })

  const receiptNumberError = computed(() => {
    return showProofOfPayment.value && !receiptNumber.value ? 'El número de comprobante es requerido' : null
  })

  /* ───────── Helpers ───────── */

  function setPlayerField(field: keyof Player, value: string | number) {
    emit('update:player', {
      ...props.player,
      [field]: String(value)
    })
  }

  /* ───────── API ───────── */

  async function checkPhone(phone: string, countryId: string) {
    const response = await backendClient.get(
      `/external-players/check?phone=${encodeURIComponent(phone)}&country_id=${countryId}&game_id=bingo&room_id=${props.selectedRoom?.id}`
    )
    return response.data
  }

  /* ───────── Proof of Payment ───────── */

  function onImageChange(file: File | null) {
    if (!file) {
      selectedReceiptFile.value = null
      imagePreview.value = null
      return
    }

    // Validar tamaño (2 MB)
    if (file.size > 2 * 1024 * 1024) {
      showToast('El archivo no debe superar los 2 MB', 'error')
      return
    }

    // Validar tipo
    if (!file.type.match(/^image\/(jpeg|jpg|png)$/)) {
      showToast('Solo se permiten archivos JPG o PNG', 'error')
      return
    }

    selectedReceiptFile.value = file
    // Crear preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }

  function clearImage() {
    imagePreview.value = null
    selectedReceiptFile.value = null
    // Limpiar el input file usando el ID
    const input = document.getElementById('image') as HTMLInputElement
    if (input) {
      input.value = ''
    }
  }

  function validateProof() {
    validationAttempted.value = true
  }

  async function uploadProof() {
    if (!imagePreview.value) {
      showToast('Por favor selecciona un comprobante', 'error')
      return
    }

    uploadingProof.value = true

    try {
      // Aquí iría la lógica para subir el comprobante
      // Por ahora solo mostramos un mensaje de éxito
      showToast('Comprobante cargado correctamente', 'success')
    } catch (error) {
      showToast('Error al cargar el comprobante', 'error')
    } finally {
      uploadingProof.value = false
    }
  }

  /* ───────── Check player ───────── */

  const clearReceiptFields = () => {
    // Limpiar imagen del comprobante
    imagePreview.value = null
    selectedReceiptFile.value = null
    
    // Limpiar número de comprobante
    receiptNumber.value = ''
    
    // Limpiar el input file
    const input = document.getElementById('image') as HTMLInputElement
    if (input) {
      input.value = ''
    }
    
    // Resetear validación
    validationAttempted.value = false
  }

  const checkExternalPlayer = async () => {
    if (!props.player.phone || !props.selectedCountry || !isPhoneValid.value) {
      return
    }

    // Limpiar campos de comprobante al verificar nuevo teléfono
    clearReceiptFields()

    checkingPlayer.value = true
    let data: any

    try {
      data = await checkPhone(props.player.phone, props.selectedCountry)

      if (data.exists) {
        playerExists.value = true

        emit('update:player', {
          ...props.player,
          firstName: data.player?.first_name ?? '',
          lastName: data.player?.last_name ?? '',
          cardCount: data.player?.card_count || 0,
          receiptNumber: receiptNumber.value
        })

        emit('update:playerExists', true)
      } else {
        playerExists.value = false
        emit('update:playerExists', false)
      }
    } catch {
      playerExists.value = false
      emit('update:playerExists', false)
    } finally {
      checkingPlayer.value = false
      phoneChecked.value = true
    }
  }

  /* ───────── Phone field with IMask ───────── */

  const selectedIso2 = computed<string | null>(() => {
    if (!props.selectedCountry) return null
    return props.countries.find(c => c.id === props.selectedCountry)?.iso_code_2 ?? null
  })

  const phoneValue = computed<string | undefined>({
    get: () => props.player.phone || undefined,
    set: val => setPlayerField('phone', val ?? '')
  })

  const { phoneMask, phonePlaceholder, phoneHint, phoneError, isPhoneValid } = usePhoneField(
    selectedIso2,
    phoneValue,
    val => setPlayerField('phone', val)
  )

  /* ───────── Watchers ───────── */

  // Watch phone changes to show popover when valid but not checked
  watch([() => props.player.phone, isPhoneValid], ([newPhone, phoneValid]) => {
    // El popover se controlará automáticamente por shouldShowPhonePopover
  })

  // Reset phone checked when phone changes
  watch(() => props.player.phone, () => {
    phoneChecked.value = false
    playerExists.value = false
    emit('update:playerExists', false)
    
    // Limpiar campos de comprobante cuando cambia el teléfono
    clearReceiptFields()
  })

  // Emitir cambios en los datos del comprobante
  watch([receiptNumber, selectedReceiptFile], ([newReceiptNumber, newReceiptFile]) => {
    emit('update:receiptData', {
      receiptNumber: newReceiptNumber,
      receiptImage: newReceiptFile
    })
  })

  defineExpose({
    onImageChange,
    clearImage,
    uploadProof,
    validateProof,
    receiptNumber,
    selectedReceiptFile
  })
</script>

<template>
  <div style="display: contents">
    <hr />

    <div class="alert alert-info d-flex align-items-center">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <div class="d-flex align-items-center mb-n1 pt-2">
        <h6>Genera los cartones <strong>solo después de confirmar el pago</strong> del jugador.</h6>
      </div>
    </div>

    <div class="row">
      <!-- País -->
      <FormSelect
        label="País"
        name="country_id"
        required
        col="col-sm-6 col-md-3"
        :options="countries.map(c => ({ value: c.id, label: c.name }))"
        optionValue="value"
        optionLabel="label"
        :modelValue="selectedCountry"
        :disabled="loadingCountries"
        :placeholder="
          loadingCountries
            ? 'Cargando países…'
            : countries.length === 0
              ? 'Sin países disponibles'
              : 'Seleccione un país'
        "
        @update:modelValue="emit('update:selectedCountry', $event)"
      />

      <!-- Teléfono -->
      <FormInput
        label="Teléfono"
        name="phone"
        type="tel"
        required
        col="col-sm-6 col-md-3"
        :mask="phoneMask ?? undefined"
        :maskEmitFormatted="true"
        :placeholder="phonePlaceholder"
        :hint="!phoneError ? phoneHint : undefined"
        :error="phoneError ?? undefined"
        :inputValidState="player.phone && isPhoneValid ? 'valid' : null"
        :disabled="!selectedCountry"
        :modelValue="player.phone"
        @update:modelValue="value => setPlayerField('phone', value)"
      >
        <!-- botón buscar -->
        <template #append>
          <VDropdown
            :shown="shouldShowPhonePopover"
            :triggers="[]"
            placement="top-start"
            :autoHide="false"
            container="body"
          >
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="!player.phone || !selectedCountry || checkingPlayer"
              :class="{ 'btn-warning': shouldShowPhonePopover }"
              @click="checkExternalPlayer"
            >
              <i v-if="!checkingPlayer" class="fas fa-search"></i>
              <i v-else class="fas fa-spinner fa-spin"></i>
            </button>
            
            <template #popper>
              <div
                class="bg-white border border-gray-200 rounded shadow-md"
                style="min-width: 180px;"
              >
                <div class="p-2">
                  <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    <span class="fw-semibold text-gray-800">Verificar teléfono</span>
                  </div>
                  <div class="text-gray-600 mb-2" style="font-size: 0.75rem;">
                    Verifica el número para continuar
                  </div>
                </div>
              </div>
            </template>
          </VDropdown>
        </template>
      </FormInput>

      <!-- Nombre -->
      <FormInput
        v-if="showNameFields"
        label="Nombre"
        name="first_name"
        type="text"
        required
        col="col-sm-6 col-md-3"
        placeholder="Nombre del jugador"
        :readonly="playerExists"
        :modelValue="player.firstName"
        @update:modelValue="value => setPlayerField('firstName', value)"
      />

      <!-- Apellido -->
      <FormInput
        v-if="showNameFields"
        label="Apellido"
        name="last_name"
        type="text"
        required
        col="col-sm-6 col-md-3"
        placeholder="Apellido del jugador"
        :readonly="playerExists"
        :modelValue="player.lastName"
        @update:modelValue="value => setPlayerField('lastName', value)"
      />

      <!-- Aviso de Jugador Registrado -->
      <div v-if="playerExists" class="col-12">
        <div class="alert alert-info d-flex align-items-center">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <div class="d-flex align-items-center mb-n1 pt-2">
            <h6>
              El jugador ya está registrado!
              <strong>Ha comprado {{ player.cardCount || 0 }} cartones</strong>
            </h6>
          </div>
        </div>
      </div>

      <!-- ── Comprobante ────────────────────────────────────────────────────────── -->
      <template v-if="showProofOfPayment">
        <div class="col-12">
          <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing: 0.05em">
            <i class="fa-solid fa-image fa-fw mr-1"></i>Comprobante
          </small>
          <hr class="mb-2 mt-1" />
        </div>

        <!-- Imagen del comprobante -->
        <div class="col-12 col-md-8">
          <div class="d-flex align-items-start" style="gap:1rem;">
            <!-- Preview / placeholder + botón quitar -->
            <div class="d-flex flex-column align-items-center" style="gap:0.5rem;">
              <div
                class="border rounded d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0"
                style="width:80px;height:80px;background:var(--ds-surface-2);"
              >
                <img
                  v-if="imagePreview"
                  :src="imagePreview"
                  alt="Preview image"
                  style="max-width:100%;max-height:100%;object-fit:contain;"
                />
                <i v-else class="fa-solid fa-image fa-2x text-muted" style="opacity:.35;"></i>
              </div>
              <button
                v-if="imagePreview"
                type="button"
                class="btn btn-sm btn-outline-danger"
                @click.prevent="clearImage"
              >
                <i class="fa-solid fa-trash fa-fw"></i> Quitar
              </button>
            </div>
            <!-- Input file -->
            <div class="flex-grow-1 d-flex flex-column">
              <FormInput
                label="Comprobante"
                id="image"
                name="image"
                type="file"
                accept="image/png, image/jpeg"
                required
                @update:modelValue="onImageChange"
                :error="imageError"
              />
              <!-- Número de comprobante -->
              <FormInput
                name="receiptNumber"
                type="text"
                placeholder="Ingrese el número de comprobante"
                required
                :modelValue="receiptNumber"
                inputClass="form-control-sm"
                :error="receiptNumberError"
                @update:modelValue="receiptNumber = $event"
              />
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
