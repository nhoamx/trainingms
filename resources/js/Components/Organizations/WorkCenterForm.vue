<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <h2 class="text-lg font-semibold text-gray-900">🏢 Centro de Trabajo Principal</h2>
      <p class="mt-1 text-sm text-gray-600">
        Este será el centro principal (Matriz) de la organización.
      </p>
    </div>

    <!-- Nombre y Tipo -->
    <div class="space-y-6">
      <FormInput
        id="work_center_name"
        name="name"
        label="Nombre del Centro"
        type="text"
        v-model="localData.name"
        :error="errors.name"
        hint='Ej: "Planta Monterrey", "Matriz CDMX"'
      />

      <FormSelect
        id="work_center_type"
        name="type"
        label="Tipo de Centro"
        v-model="localData.type"
        :options="workCenterTypes"
        :error="errors.type"
      />
    </div>

    <!-- Sección: Ubicación -->
    <div class="border-t border-gray-200 pt-6">
      <h3 class="text-base font-medium text-gray-900 mb-4">📍 Ubicación</h3>
      <div class="space-y-6">
        <FormInput
          id="street_address"
          name="street_address"
          label="Calle y Número"
          type="text"
          v-model="localData.street_address"
          :error="errors.street_address"
          autocomplete="street-address"
          hint="Opcional • Puedes completarlo después"
        />

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <FormInput
            id="neighborhood"
            name="neighborhood"
            label="Colonia"
            type="text"
            v-model="localData.neighborhood"
            :error="errors.neighborhood"
            hint="Opcional"
          />

          <FormInput
            id="postal_code"
            name="postal_code"
            label="Código Postal"
            type="text"
            v-model="localData.postal_code"
            :error="errors.postal_code"
            autocomplete="postal-code"
            hint="Opcional"
          />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <FormInput
            id="municipality"
            name="municipality"
            label="Municipio"
            type="text"
            v-model="localData.municipality"
            :error="errors.municipality"
            hint="Opcional"
          />

          <FormInput
            id="state"
            name="state"
            label="Estado"
            type="text"
            v-model="localData.state"
            :error="errors.state"
            hint="Opcional"
          />
        </div>
      </div>
    </div>

    <!-- Sección: Datos Fiscales -->
    <div class="border-t border-gray-200 pt-6">
      <h3 class="text-base font-medium text-gray-900 mb-4">📄 Datos Fiscales</h3>
      <div class="space-y-6">
        <div>
          <FormInput
            id="legal_name"
            name="legal_name"
            label="Razón Social"
            type="text"
            v-model="localData.legal_name"
            :error="errors.legal_name"
            hint="Nombre legal registrado ante el SAT"
            @focus="suggestLegalName"
          />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="tax_id" class="block text-sm font-medium text-gray-900">
              RFC
              <span class="text-red-500">*</span>
            </label>
            <div class="relative mt-2">
              <input
                id="tax_id"
                name="tax_id"
                type="text"
                v-model="localData.tax_id"
                maxlength="13"
                placeholder="ABC123456XYZ"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 sm:text-sm/6 transition-colors"
                :class="{
                  'outline-gray-300 focus:outline-indigo-600': !localData.tax_id || rfcValidationState === null,
                  'outline-green-500 focus:outline-green-600': rfcValidationState === true,
                  'outline-red-500 focus:outline-red-600': rfcValidationState === false,
                }"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <CheckCircleIcon 
                  v-if="rfcValidationState === true"
                  class="h-5 w-5 text-green-500"
                />
                <XCircleIcon 
                  v-if="rfcValidationState === false"
                  class="h-5 w-5 text-red-500"
                />
              </div>
            </div>
            <p v-if="errors.tax_id" class="mt-1 text-xs text-red-500">
              {{ errors.tax_id }}
            </p>
            <p v-else class="mt-1 text-xs" :class="rfcValidationState === true ? 'text-green-600' : 'text-gray-500'">
              {{ rfcValidationState === true ? '✓ RFC válido' : 'Registro Federal de Contribuyentes (13 caracteres)' }}
            </p>
          </div>

          <FormInput
            id="employer_registration"
            name="employer_registration"
            label="Registro Patronal"
            type="text"
            v-model="localData.employer_registration"
            :error="errors.employer_registration"
            hint="Opcional • Número de registro ante el IMSS"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid'
import FormInput from '../FormInput.vue'
import FormSelect from '../FormSelect.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue'])

const workCenterTypes = [
  { value: 'headquarters', label: 'Matriz' },
  { value: 'branch', label: 'Sucursal' },
  { value: 'plant', label: 'Planta' },
  { value: 'warehouse', label: 'Almacén' },
  { value: 'office', label: 'Oficina' },
  { value: 'other', label: 'Otro' },
]

const localData = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  },
})

// RFC Validation Pattern (Mexican Tax ID)
const rfcPattern = /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/

// Computed property for RFC validation state
const rfcValidationState = computed(() => {
  if (!localData.value.tax_id || localData.value.tax_id.length === 0) {
    return null // No input yet
  }
  if (localData.value.tax_id.length < 13) {
    return null // Still typing
  }
  return rfcPattern.test(localData.value.tax_id)
})

// Watch RFC input to format automatically
watch(() => localData.value.tax_id, (newValue) => {
  if (newValue) {
    // Convert to uppercase and remove invalid characters
    const formatted = newValue
      .toUpperCase()
      .replace(/[^A-Z0-9Ñ&]/g, '')
      .slice(0, 13)
    
    if (formatted !== newValue) {
      localData.value.tax_id = formatted
    }
  }
})

// Auto-suggest legal name based on work center name
const suggestLegalName = () => {
  if (!localData.value.legal_name && localData.value.name) {
    // Only suggest if legal_name is empty and center name exists
    const centerName = localData.value.name.trim()
    if (centerName && !centerName.match(/s\.?a\.?|s\.?c\.?|c\.?v\.?/i)) {
      localData.value.legal_name = `${centerName} S.A. de C.V.`
    }
  }
}
</script>
