<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Evaluación en Línea</h1>
        <p class="text-lg text-gray-600">Sistema de Evaluación Psicosocial</p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow-xl rounded-lg sm:px-10">
        <form @submit.prevent="accessEvaluation" class="space-y-6">
          <div>
            <label for="folio" class="block text-sm font-medium text-gray-700">
              Número de Folio
            </label>
            <div class="mt-1">
              <input
                id="folio"
                name="folio"
                type="text"
                v-model="form.folio"
                required
                maxlength="10"
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="Ingresa tu número de folio"
              />
              <p v-if="form.errors.folio" class="mt-2 text-sm text-red-600">
                {{ form.errors.folio }}
              </p>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing || !form.folio"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="form.processing">Verificando...</span>
              <span v-else>Acceder a Evaluación</span>
            </button>
          </div>
        </form>

        <!-- Información adicional -->
        <div class="mt-8 border-t border-gray-200 pt-6">
          <div class="text-center">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Instrucciones</h3>
            <div class="text-xs text-gray-600 space-y-2">
              <p>• Ingresa el número de folio que te fue proporcionado</p>
              <p>• Asegúrate de tener tiempo suficiente para completar la evaluación</p>
              <p>• Una vez iniciada, debes completar toda la evaluación</p>
              <p>• Si tienes problemas, contacta al administrador</p>
            </div>
          </div>
        </div>

        <!-- Información de contacto -->
        <div class="mt-6 text-center">
          <p class="text-xs text-gray-500">
            ¿Problemas con tu folio? 
            <a href="mailto:soporte@evaluacion.com" class="text-indigo-600 hover:text-indigo-500">
              Contacta soporte
            </a>
          </p>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center">
      <p class="text-sm text-gray-500">
        Sistema de Evaluación Psicosocial © {{ new Date().getFullYear() }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  folio: ''
})

const accessEvaluation = () => {
  // Redirigir directamente a la evaluación con el folio
  window.location.href = `/evaluacion-online/${form.folio}`
}
</script>