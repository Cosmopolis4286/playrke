<script setup lang="ts" name="AdminTreasurerForm">
import { computed, ref, watch, nextTick } from "vue";

// Components
import FormInput from "@/js/Components/Common/Form/FormInput.vue";
import FormSelect from "@/js/Components/Common/Form/FormSelect.vue";
import FormSwitch from "@/js/Components/Common/Form/FormSwitch.vue";

// Composables
import { useAdminTreasurerForm } from "@/js/composables/roles/admin/treasurer/useAdminTreasurerForm";
import { usePhoneField } from "@/js/composables/form/usePhoneField";

// Types
import type { Currency } from "@/js/types/currency";
import type { Country } from "@/js/types/country";
import type { TimeZone } from "@/js/types/time-zone";
import type { Game } from "@/js/types/game";
import type { Treasurer } from "@/js/types/roles/treasurer/treasurer";

/* ==========================================================================
 * Props
 * ========================================================================== */

const props = defineProps<{
  countries: Country[];
  currencies: Currency[];
  timeZones: TimeZone[];
  games: Game[];
  modelValue: Partial<Treasurer>;
}>();

/* ==========================================================================
 * Emits
 * ========================================================================== */

const emit = defineEmits<{
  (e: "submit"): void;
  (e: "update:modelValue", value: Partial<Treasurer>): void;
}>();

/* ==========================================================================
 * Form logic
 * ========================================================================== */

const { form, errors, isValid } = useAdminTreasurerForm(
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
 * Teléfono — usePhoneField
 * ========================================================================== */

const selectedIso2 = computed<string | null>(() => {
  if (!form.value.country_id) return null;
  // eslint-disable-next-line eqeqeq
  const match = props.countries.find((c) => c.id == form.value.country_id);
  return match?.iso_code_2 ?? null;
});

const phoneRef = ref<string>(form.value.phone ?? "");

watch(
  () => form.value.phone,
  (v) => { if (v !== phoneRef.value) phoneRef.value = v ?? ""; }
);

watch(phoneRef, (v) => {
  if (v !== form.value.phone) {
    form.value = { ...form.value, phone: v };
  }
});

const {
  phoneMask,
  phonePlaceholder,
  phoneHint,
  phoneDialPrefix,
  phoneError,
  isPhoneValid,
} = usePhoneField(
  selectedIso2,
  phoneRef,
  (v) => { phoneRef.value = v; }
);

watch(selectedIso2, (iso, prevIso) => {
  if (iso && iso !== prevIso) {
    nextTick(() => {
      if (!phoneRef.value?.trim()) {
        phoneRef.value = phoneDialPrefix.value;
      }
    });
  }
});

const phoneErrorDisplay = computed(
  () => phoneError.value ?? errors.value.phone ?? null
);

const phoneValidState = computed<"valid" | "invalid" | null>(() => {
  if (!phoneRef.value?.trim()) return null;
  return isPhoneValid.value ? "valid" : "invalid";
});

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
  <form id="treasurer-form" novalidate @submit.prevent="submit">
    <div class="row">

      <!-- ── Datos de acceso ─────────────────────────────────────────────── -->
      <div class="col-12 mb-1">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-user fa-fw mr-1"></i>Datos de acceso
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <FormInput
        label="Nombre completo"
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

      <!-- ── Configuración comercial ─────────────────────────────────────── -->
      <div class="col-12 mb-1 mt-2">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-gear fa-fw mr-1"></i>Configuración
        </small>
        <hr class="mt-1 mb-2" />
      </div>

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

      <FormInput
        label="Teléfono"
        name="phone"
        required
        col="col-sm-6"
        :placeholder="phonePlaceholder"
        :mask="phoneMask"
        :maskEmitFormatted="true"
        :disabled="!form.country_id"
        :inputValidState="phoneValidState"
        :error="phoneErrorDisplay"
        :hint="!phoneErrorDisplay && form.country_id ? phoneHint : (!form.country_id ? 'Selecciona un país primero' : undefined)"
        v-model="phoneRef"
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
        :error="errors.allowed_games"
        v-model="form.allowed_games"
      />

    </div>
  </form>
</template>
