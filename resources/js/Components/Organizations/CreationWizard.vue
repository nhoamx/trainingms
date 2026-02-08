<template>
  <div class="max-w-4xl mx-auto">
    <!-- Progress Bar -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">
          Paso {{ currentStep }} de 2
        </span>
        <span class="text-sm text-gray-500">
          {{ currentStep === 1 ? 'Datos Corporativos' : 'Centro de Trabajo Principal' }}
        </span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div 
          class="h-full bg-indigo-600 transition-all duration-300"
          :style="{ width: `${(currentStep / 2) * 100}%` }"
        />
      </div>
    </div>

    <!-- Step 1: Organization Data -->
    <div v-show="currentStep === 1">
      <OrganizationForm 
        v-model="organizationData"
        :errors="organizationErrors"
      />
    </div>

    <!-- Step 2: Work Center Data -->
    <div v-show="currentStep === 2">
      <WorkCenterForm 
        v-model="workCenterData"
        :errors="workCenterErrors"
      />
    </div>

    <!-- Navigation -->
    <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
      <button
        v-if="currentStep > 1"
        type="button"
        @click="previousStep"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <ChevronLeftIcon class="w-5 h-5 mr-1" />
        Atrás
      </button>
      <div v-else />

      <button
        v-if="currentStep < 2"
        type="button"
        @click="nextStep"
        :disabled="!canProceed"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Siguiente
        <ChevronRightIcon class="w-5 h-5 ml-1" />
      </button>

      <button
        v-else
        type="button"
        @click="submit"
        :disabled="processing"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        <span v-if="processing">Creando...</span>
        <span v-else>Crear Organización</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import OrganizationForm from './OrganizationForm.vue'
import WorkCenterForm from './WorkCenterForm.vue'

const currentStep = ref(1)
const processing = ref(false)

const organizationData = ref({
  name: '',
  logo: null,
  actividad_principal: '',
})

const workCenterData = ref({
  name: '',
  type: 'headquarters',
  street_address: '',
  neighborhood: '',
  postal_code: '',
  municipality: '',
  state: '',
  legal_name: '',
  tax_id: '',
  employer_registration: '',
})

const organizationErrors = reactive({})
const workCenterErrors = reactive({})

const canProceed = computed(() => {
  if (currentStep.value === 1) {
    return organizationData.value.name.trim().length > 0
  }
  if (currentStep.value === 2) {
    return workCenterData.value.name.trim().length > 0
  }
  return true
})

function nextStep() {
  if (currentStep.value < 2 && canProceed.value) {
    currentStep.value++
  }
}

function previousStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

function submit() {
  // Usar FormData para enviar los datos (incluido el logo)
  const formData = new FormData()
  
  // Agregar datos de organización
  formData.append('name', organizationData.value.name)
  if (organizationData.value.actividad_principal) {
    formData.append('actividad_principal', organizationData.value.actividad_principal)
  }
  if (organizationData.value.logo) {
    formData.append('logo', organizationData.value.logo)
  }
  
  // Agregar datos del work center con prefijo wc_ para evitar conflicto de nombres
  Object.keys(workCenterData.value).forEach(key => {
    const value = workCenterData.value[key]
    if (value !== null && value !== '') {
      formData.append(`wc_${key}`, value)
    }
  })

  processing.value = true

  router.post(route('organizations.store'), formData, {
    preserveScroll: true,
    forceFormData: true,
    onStart: () => {
      processing.value = true
    },
    onFinish: () => {
      processing.value = false
    },
    onError: (serverErrors) => {
      processing.value = false
      
      // Limpiar errores previos
      Object.keys(organizationErrors).forEach(key => delete organizationErrors[key])
      Object.keys(workCenterErrors).forEach(key => delete workCenterErrors[key])

      // Separar errores por sección
      const orgFields = ['name', 'logo', 'actividad_principal']
      
      Object.keys(serverErrors).forEach(key => {
        if (orgFields.includes(key)) {
          organizationErrors[key] = serverErrors[key]
          if (currentStep.value === 2) {
            currentStep.value = 1 // Volver a paso 1 si hay errores ahí
          }
        } else {
          workCenterErrors[key] = serverErrors[key]
        }
      })
    },
  })
}
</script>
