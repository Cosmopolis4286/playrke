<script setup lang="ts" name="AdminCustomerForm">
import { computed, ref, watch, nextTick } from "vue";

// Components
import FormInput from "@/js/Components/Common/Form/FormInput.vue";
import FormSelect from "@/js/Components/Common/Form/FormSelect.vue";
import FormSwitch from "@/js/Components/Common/Form/FormSwitch.vue";

// Composables
import { useAdminCustomerForm } from "@/js/composables/roles/admin/customer/useAdminCustomerForm";
import { usePhoneField } from "@/js/composables/form/usePhoneField";

// Types
import type { Currency } from "@/js/types/currency";
import type { Country } from "@/js/types/country";
import type { TimeZone } from "@/js/types/time-zone";
import type { Game } from "@/js/types/game";
import type { Customer } from "@/js/types/roles/customer/customer";

/* ==========================================================================
 * Props
 * ========================================================================== */

const props = defineProps<{
  countries: Country[];
  currencies: Currency[];
  timeZones: TimeZone[];
  games: Game[];
  modelValue: Partial<Customer>;
  generatingKey?: boolean;
}>();

/* ==========================================================================
 * Emits
 * ========================================================================== */

const emit = defineEmits<{
  (e: "submit"): void;
  (e: "update:modelValue", value: Partial<Customer>): void;
  (e: "generate-key"): void;
}>();

/* ==========================================================================
 * Form logic
 * ========================================================================== */

const { form, errors, isValid, isEditMode } = useAdminCustomerForm(
  () => props.modelValue,
  emit
);

/* ==========================================================================
 * Máscara comisión
 * ========================================================================== */

const commissionMask = {
  mask: Number,
  scale: 2,
  min: 0,
  max: 100,
  radix: ".",
  mapToRadix: [","],
  padFractionalZeros: true,
  normalizeZeros: true,
  thousandsSeparator: "",
};

/* ==========================================================================
 * Ruta API — estado visual
 * ========================================================================== */

const apiRouteState = computed<"valid" | "invalid" | null>(() => {
  const val = form.value.api_route?.trim();
  if (!val) return null;
  try {
    const url = new URL(val);
    return url.protocol === "https:" || url.protocol === "http:" ? "valid" : "invalid";
  } catch {
    return "invalid";
  }
});

/* ==========================================================================
 * Teléfono — usePhoneField
 *
 * Necesitamos un ref local sincronizado con form.phone porque
 * usePhoneField necesita un Ref mutable (no un ComputedRef).
 * ========================================================================== */

/** ISO-2 del país seleccionado */
const selectedIso2 = computed<string | null>(() => {
  if (!form.value.country_id) return null;
  const match = props.countries.find(
    // eslint-disable-next-line eqeqeq
    (c) => c.id == form.value.country_id
  );
  return match?.iso_code_2 ?? null;
});

/** Ref local del teléfono — fuente de verdad para usePhoneField */
const phoneRef = ref<string>(form.value.phone ?? "");

// form → phoneRef  (ej: al cargar en modo edición)
watch(
  () => form.value.phone,
  (v) => {
    if (v !== phoneRef.value) phoneRef.value = v ?? "";
  }
);

// phoneRef → form
watch(phoneRef, (v) => {
  if (v !== form.value.phone) {
    form.value = { ...form.value, phone: v };
  }
});

const {
  phoneMask,
  phonePlaceholder,
  phoneHint,
  phoneError,
  isPhoneValid,
  onPhoneFocus,
} = usePhoneField(
  selectedIso2,
  phoneRef,
  (v) => { phoneRef.value = v; }
);

/** Error visible: validación de formato primero, luego campo requerido */
const phoneErrorDisplay = computed(
  () => phoneError.value ?? errors.value.phone ?? null
);

/** Estado visual del campo teléfono */
const phoneValidState = computed<"valid" | "invalid" | null>(() => {
  if (!phoneRef.value?.trim()) return null;
  return isPhoneValid.value ? "valid" : "invalid";
});

/* ==========================================================================
 * Logo — preview
 * ========================================================================== */

const logoPreview = ref<string | null>(null);

function onLogoChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) {
    logoPreview.value = null;
    form.value = { ...form.value, logo: undefined };
    return;
  }
  const reader = new FileReader();
  reader.onload = (e) => { logoPreview.value = e.target?.result as string; };
  reader.readAsDataURL(file);
  form.value = { ...form.value, logo: file as any };
}

function clearLogo() {
  logoPreview.value = null;
  form.value = { ...form.value, logo: undefined };
}

watch(
  () => form.value.logo,
  (val) => {
    if (typeof val === "string" && val) logoPreview.value = val;
    else if (!val) logoPreview.value = null;
  },
  { immediate: true }
);

/* ==========================================================================
 * Filtered currencies / timezones by country
 * ========================================================================== */

const filteredCurrencies = computed(() => {
  if (!form.value.country_id) return [];
  // eslint-disable-next-line eqeqeq
  return props.currencies.filter((c) => c.country_id == form.value.country_id);
});

const filteredTimeZones = computed(() => {
  if (!form.value.country_id) return [];
  // eslint-disable-next-line eqeqeq
  return props.timeZones.filter((tz) => tz.country_id == form.value.country_id);
});

const hasSingleCurrency = computed(() => filteredCurrencies.value.length === 1);
const hasSingleTimeZone = computed(() => filteredTimeZones.value.length === 1);

const otherCurrenciesOptions = computed(() => {
  if (!form.value.currency_id) return props.currencies;
  // eslint-disable-next-line eqeqeq
  return props.currencies.filter((c) => c.id != form.value.currency_id);
});

/* ==========================================================================
 * Watchers
 * ========================================================================== */

watch(
  () => form.value.country_id,
  async (newVal, oldVal) => {
    if (oldVal !== undefined) {
      form.value = {
        ...form.value,
        currency_id: null,
        time_zone_id: null,
        other_currencies: [],
      };
    }
    if (!newVal) return;
    await nextTick();

    // eslint-disable-next-line eqeqeq
    const currencies = props.currencies.filter((c) => c.country_id == newVal);
    // eslint-disable-next-line eqeqeq
    const timeZones = props.timeZones.filter((tz) => tz.country_id == newVal);

    form.value = {
      ...form.value,
      currency_id: currencies.length === 1 ? currencies[0].id : form.value.currency_id,
      time_zone_id: timeZones.length === 1 ? timeZones[0].id : form.value.time_zone_id,
    };
  },
  { immediate: true }
);

watch(
  () => form.value.currency_id,
  (newVal, oldVal) => {
    if (oldVal !== undefined && newVal !== oldVal) {
      form.value = { ...form.value, other_currencies: [] };
    }
  }
);

watch(
  () => form.value.has_commission,
  (val) => { if (!val) form.value = { ...form.value, commission: "" }; }
);

/* ==========================================================================
 * Submit
 * ========================================================================== */

const submit = (): boolean => {
  if (!isValid.value) return false;
  emit("submit");
  return true;
};

defineExpose({ submit, isValid });
</script>

<template>
  <form id="commerce-form" novalidate @submit.prevent="submit">
    <div class="row">

      <!-- ── Datos de acceso ─────────────────────────────────────────────── -->
      <div class="col-12 mb-1">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-user fa-fw mr-1"></i>Datos de acceso
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <FormInput
        label="Razón Social"
        name="name"
        required
        col="col-sm-6"
        :error="errors.name"
        v-model="form.name"
      />

      <FormInput
        label="Username"
        name="username"
        required
        minlength="5"
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
        col="col-12"
        :error="errors.email"
        v-model="form.email"
      />

      <!-- ── Integración API ─────────────────────────────────────────────── -->
      <div class="col-12 mb-1 mt-2">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-plug fa-fw mr-1"></i>Integración API
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <FormInput
        label="Ruta API"
        name="api_route"
        type="url"
        required
        col="col-12"
        placeholder="https://www.api.com/"
        :error="errors.api_route"
        :inputValidState="!errors.api_route && apiRouteState === 'valid' ? 'valid' : null"
        v-model="form.api_route"
      >
        <template #prepend>
          <span class="input-group-text px-2">
            <i
              :class="[
                'fa-solid fa-fw',
                apiRouteState === 'valid'
                  ? 'fa-circle-check text-success'
                  : apiRouteState === 'invalid'
                  ? 'fa-circle-xmark text-danger'
                  : 'fa-link text-muted',
              ]"
            ></i>
          </span>
        </template>
      </FormInput>

      <FormInput
        v-if="isEditMode"
        label="Llave de Conexión"
        name="customer_key"
        col="col-12"
        :disabled="true"
        :readonly="true"
        inputClass="font-monospace text-muted"
        v-model="form.customer_key"
      >
        <template #append>
          <button
            class="btn btn-outline-secondary"
            type="button"
            title="Regenerar llave de conexión"
            :disabled="props.generatingKey"
            @click.prevent="emit('generate-key')"
          >
            <i
              :class="['fa-solid', props.generatingKey ? 'fa-spinner fa-spin' : 'fa-rotate']"
            ></i>
            <span class="ml-1 d-none d-sm-inline">
              {{ props.generatingKey ? "Generando…" : "Regenerar" }}
            </span>
          </button>
        </template>
      </FormInput>

      <!-- ── Configuración comercial ─────────────────────────────────────── -->
      <div class="col-12 mb-1 mt-2">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-gear fa-fw mr-1"></i>Configuración comercial
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <!--
        País y Teléfono en la misma fila (col-sm-6 cada uno).
        Al cambiar el país, usePhoneField actualiza la máscara,
        placeholder, validación y limpia el teléfono automáticamente.
      -->
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

      <!-- Teléfono: máscara IMask + validación dinámicas según el país -->
      <FormInput
        label="Teléfono"
        name="phone"
        required
        col="col-sm-6"
        :placeholder="form.country_id ? phonePlaceholder : 'Selecciona un país primero'"
        :mask="phoneMask"
        :disabled="!form.country_id"
        :inputValidState="phoneValidState"
        :error="phoneErrorDisplay"
        v-model="phoneRef"
        @focus="() => onPhoneFocus(phoneRef, (v) => { phoneRef = v; })"
      >
        <template #prepend>
          <span class="input-group-text px-2">
            <i class="fa-solid fa-phone fa-fw text-muted"></i>
          </span>
        </template>
      </FormInput>

      <FormSelect
        v-if="form.country_id"
        label="Zona Horaria"
        name="time_zone_id"
        required
        :placeholder="hasSingleTimeZone ? undefined : 'Seleccionar zona horaria'"
        :options="filteredTimeZones"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.time_zone_id"
      />

      <FormSelect
        v-if="form.country_id"
        label="Moneda Principal"
        name="currency_id"
        required
        :placeholder="hasSingleCurrency ? undefined : 'Seleccionar moneda principal'"
        :options="filteredCurrencies"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        :error="errors.currency_id"
        v-model="form.currency_id"
      />

      <FormSelect
        v-if="form.currency_id"
        label="Otras Monedas"
        name="other_currencies"
        multiple
        select2
        placeholder="Seleccionar otras monedas"
        :options="otherCurrenciesOptions"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        v-model="form.other_currencies"
      />

      <!-- Switch comisión + campo condicional en la misma fila -->
      <FormSwitch
        label="¿Posee comisión?"
        name="has_commission"
        col="col-sm-6"
        class="d-flex align-items-end pb-3"
        v-model="form.has_commission"
      />

      <FormInput
        v-if="form.has_commission"
        label="Comisión %"
        name="commission"
        type="text"
        required
        col="col-sm-6"
        placeholder="0.00"
        :mask="commissionMask"
        :error="errors.commission"
        v-model="form.commission"
      />

      <FormSelect
        label="Juegos Permitidos"
        name="allowed_games"
        required
        multiple
        select2
        placeholder="Seleccionar juegos"
        :options="props.games"
        optionValue="id"
        optionLabel="name"
        col="col-12"
        v-model="form.allowed_games"
      />

      <!-- ── Logo ────────────────────────────────────────────────────────── -->
      <div class="col-12 mb-1 mt-2">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-image fa-fw mr-1"></i>Logo
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <div class="col-12">
        <div class="form-group">
          <label for="logo">
            Logo <small class="text-muted">(PNG o JPG, máx. 2 MB)</small>
          </label>
          <div class="d-flex align-items-start" style="gap:1rem;">
            <!-- Preview / placeholder -->
            <div
              class="border rounded d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0"
              style="width:80px;height:80px;background:#f8f9fa;"
            >
              <img
                v-if="logoPreview"
                :src="logoPreview"
                alt="Preview logo"
                style="max-width:100%;max-height:100%;object-fit:contain;"
              />
              <i v-else class="fa-solid fa-image fa-2x text-muted" style="opacity:.35;"></i>
            </div>
            <!-- Input file + botón quitar -->
            <div class="flex-grow-1">
              <input
                id="logo"
                name="logo"
                type="file"
                class="form-control-file"
                accept="image/png, image/jpeg"
                @change="onLogoChange"
              />
              <button
                v-if="logoPreview"
                type="button"
                class="btn btn-sm btn-outline-danger mt-1"
                @click.prevent="clearLogo"
              >
                <i class="fa-solid fa-trash fa-fw"></i> Quitar logo
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </form>
</template>
