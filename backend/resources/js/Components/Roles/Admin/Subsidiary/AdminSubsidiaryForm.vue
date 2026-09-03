<script setup lang="ts" name="AdminSubsidiaryForm">
import { computed, ref, watch, nextTick } from "vue";

// Components
import FormInput from "@js/Components/Common/Form/FormInput.vue";
import FormSelect from "@js/Components/Common/Form/FormSelect.vue";
import FormSwitch from "@js/Components/Common/Form/FormSwitch.vue";

// Composables
import { useAdminSubsidiaryForm } from "@js/composables/roles/admin/subsidiary/useAdminSubsidiaryForm";
import { usePhoneField } from "@js/composables/form/usePhoneField";

// Types
import type { Currency } from "@js/types/currency";
import type { Country } from "@js/types/country";
import type { Subsidiary } from "@js/types/roles/subsidiary/subsidiary";
import type { SubsidiaryCommerceOption } from "@js/types/roles/admin/admin-subsidiary";

/* ==========================================================================
 * Props
 * ========================================================================== */

const props = defineProps<{
  commerces: SubsidiaryCommerceOption[];
  countries: Country[];
  currencies: Currency[];
  modelValue: Partial<Subsidiary>;
}>();

/* ==========================================================================
 * Emits
 * ========================================================================== */

const emit = defineEmits<{
  (e: "submit"): void;
  (e: "update:modelValue", value: Partial<Subsidiary>): void;
}>();

/* ==========================================================================
 * Form logic
 * ========================================================================== */

const { form, errors, isValid, isEditMode } = useAdminSubsidiaryForm(
  () => props.modelValue,
  emit
);

/* ==========================================================================
 * Comercio único (usuario 'commerce' gestionando sus propias sucursales)
 * — el backend ya acota props.commerces a un solo elemento en ese caso
 * (ver AdminSubsidiaryContextService::subsidiaryContext()). Con una sola
 * opción, mostrar el select igual fuerza al commerce a "elegirse a sí
 * mismo" — se preselecciona y se oculta, mismo patrón que hasSingleCurrency
 * ya usa más abajo para moneda.
 * ========================================================================== */

const hasSingleCommerce = computed(() => props.commerces.length === 1);

watch(
  () => props.commerces,
  (list) => {
    if (list.length === 1 && !form.value.commerce_id) {
      form.value = { ...form.value, commerce_id: list[0].id };
    }
  },
  { immediate: true }
);

/**
 * Comercios no ACTIVE se marcan en el label — el admin ve TODOS los
 * comercios (no solo activos, ver AdminSubsidiaryContextService::
 * subsidiaryContext()) para que editar una sucursal cuyo comercio padre
 * fue desactivado/bloqueado siga mostrando esa opción en el select en vez
 * de dejarlo vacío. `status` solo viene presente en el catálogo de admin
 * (rol commerce ya se ve solo a sí mismo, siempre ACTIVE por construcción).
 */
const commerceOptions = computed(() =>
  props.commerces.map((c) => ({
    ...c,
    name: c.status && c.status !== "ACTIVE" ? `${c.name} (${c.status})` : c.name,
  }))
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
  const match = props.countries.find((c) => c.id === form.value.country_id);
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
 * Filtered currencies by country
 * ========================================================================== */

const filteredCurrencies = computed(() => {
  if (!form.value.country_id) return [];
  return props.currencies.filter((c) => c.country_id === form.value.country_id);
});

const hasSingleCurrency = computed(() => filteredCurrencies.value.length === 1);

/* ==========================================================================
 * Watchers
 * ========================================================================== */

watch(
  () => form.value.country_id,
  async (newVal, oldVal) => {
    if (oldVal !== undefined) {
      form.value = { ...form.value, currency_id: null };
    }
    if (!newVal) return;
    await nextTick();

    const currencies = props.currencies.filter((c) => c.country_id === newVal);

    form.value = {
      ...form.value,
      currency_id: currencies.length === 1 ? currencies[0].id : form.value.currency_id,
    };
  },
  { immediate: true }
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
  <form id="subsidiary-form" novalidate @submit.prevent="submit">
    <div class="row">

      <!-- ── Datos de acceso ─────────────────────────────────────────────── -->
      <div class="col-12 mb-1">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-user fa-fw mr-1"></i>Datos de acceso
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <FormInput
        label="Nombre de la sucursal"
        name="name"
        required
        col="col-sm-6"
        :error="errors.name"
        v-model="form.name"
      />

      <FormInput
        label="Username"
        name="username"
        :required="!isEditMode"
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
        :required="!isEditMode"
        col="col-12"
        :error="errors.email"
        v-model="form.email"
      />

      <!-- ── Jerarquía ────────────────────────────────────────────────────── -->
      <div class="col-12 mb-1 mt-2">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-sitemap fa-fw mr-1"></i>Jerarquía
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <FormSelect
        v-if="!hasSingleCommerce"
        label="Comercio (comercializadora)"
        name="commerce_id"
        required
        placeholder="Seleccionar comercio"
        :options="commerceOptions"
        optionValue="id"
        optionLabel="name"
        col="col-12"
        :error="errors.commerce_id"
        v-model="form.commerce_id"
      />

      <!-- Con un solo comercio disponible (usuario 'commerce' gestionando
           sus propias sucursales) el select de arriba no se muestra — sin
           esto la sección "Jerarquía" quedaba como un encabezado sin
           ningún campo debajo. Texto de solo lectura en su lugar. -->
      <div v-else class="col-12">
        <label class="form-label small text-muted mb-1">Comercio (comercializadora)</label>
        <p class="mb-0 font-weight-bold">
          <i class="fa-solid fa-store fa-fw mr-1 text-muted"></i>{{ props.commerces[0]?.name ?? '—' }}
        </p>
      </div>

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
        label="Moneda"
        name="currency_id"
        required
        :placeholder="hasSingleCurrency ? undefined : 'Seleccionar moneda'"
        :options="filteredCurrencies"
        optionValue="id"
        optionLabel="name"
        col="col-sm-6"
        :error="errors.currency_id"
        v-model="form.currency_id"
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

      <!-- ── Datos opcionales ─────────────────────────────────────────────── -->
      <div class="col-12 mb-1 mt-2">
        <small class="text-muted text-uppercase font-weight-bold" style="letter-spacing:.05em;">
          <i class="fa-solid fa-tag fa-fw mr-1"></i>Datos adicionales (opcional)
        </small>
        <hr class="mt-1 mb-2" />
      </div>

      <FormInput
        label="Marca"
        name="brand"
        col="col-sm-4"
        v-model="form.brand"
      />

      <FormInput
        label="Producto"
        name="product"
        col="col-sm-4"
        v-model="form.product"
      />

      <FormInput
        label="Link / Ruta"
        name="link_route"
        col="col-sm-4"
        v-model="form.link_route"
      />

    </div>
  </form>
</template>
