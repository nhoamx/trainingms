<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h2 class="text-lg font-semibold text-gray-900">📄 Información Corporativa</h2>
      <p class="mt-1 text-sm text-gray-600">
        Ingresa los datos generales del corporativo o empresa.
      </p>
    </div>

    <!-- Nombre de la Organización (obligatorio) -->
    <FormInput
      id="name"
      name="name"
      label="Nombre del Corporativo"
      type="text"
      v-model="localData.name"
      :error="errors.name"
      autocomplete="organization"
    />

    <!-- Actividad Principal (opcional) -->
    <FormInput
      id="actividad_principal"
      name="actividad_principal"
      label="Actividad Principal (opcional)"
      type="text"
      v-model="localData.actividad_principal"
      :error="errors.actividad_principal"
      hint="Giro de la empresa"
    />

    <!-- Logo Upload (opcional) -->
    <div>
      <label class="block text-sm font-medium text-gray-900">
        Logo (opcional)
      </label>
      <div 
        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10"
        @drop.prevent="handleFileDrop"
        @dragover.prevent="handleDragOver"
      >
        <div class="text-center">
          <PhotoIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
          <div class="mt-4 flex text-sm leading-6 text-gray-600">
            <label
              for="logo-upload"
              class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500"
            >
              <span>Subir un archivo</span>
              <input
                id="logo-upload"
                name="logo"
                type="file"
                class="sr-only"
                accept="image/*"
                @change="handleFileUpload"
              />
            </label>
            <p class="pl-1">o arrastrar aquí</p>
          </div>
          <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF hasta 10MB</p>
          <p v-if="localData.logo" class="mt-2 text-sm text-green-600">
            ✓ {{ localData.logo.name }}
          </p>
        </div>
      </div>
      <p v-if="errors.logo" class="mt-1 text-xs text-red-500">
        {{ errors.logo }}
      </p>
    </div>

    <!-- Info Box: Folio autogenerado -->
    <div class="rounded-md bg-blue-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <InformationCircleIcon class="h-5 w-5 text-blue-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-blue-800">
            Folio autogenerado
          </h3>
          <div class="mt-2 text-sm text-blue-700">
            <p>
              El folio de la organización se generará automáticamente al crear el registro. 
              No es necesario ingresarlo manualmente.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { PhotoIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import FormInput from '../FormInput.vue'

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

const localData = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  },
})

function handleFileDrop(e) {
  const files = e.dataTransfer.files
  if (files.length > 0) {
    localData.value.logo = files[0]
  }
}

function handleDragOver(e) {
  // Necesario para permitir el drop
}

function handleFileUpload(e) {
  const files = e.target.files
  if (files.length > 0) {
    localData.value.logo = files[0]
  }
}
</script>
