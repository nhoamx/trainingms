<template>
  <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header de éxito -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
            <CheckCircleIcon class="h-6 w-6 text-green-600" />
          </div>
          <h1 class="text-3xl font-bold text-gray-900">¡Evaluación Completada!</h1>
          <p class="mt-2 text-lg text-gray-600">
            Tu evaluación ha sido guardada exitosamente
          </p>
        </div>
      </div>

      <!-- Información de la evaluación -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Detalles de la Evaluación</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <dt class="text-sm font-medium text-gray-500">Folio</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ evaluation.folio }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">ID Personal</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ evaluation.personal_id }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Organización</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ evaluation.organization?.name || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Tipo de Evaluación</dt>
            <dd class="mt-1 text-sm text-gray-900">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Guía {{ evaluation.reference_guide }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Fecha de Realización</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Total de Respuestas</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ evaluation.questions?.length || 0 }}</dd>
          </div>
        </div>
      </div>

      <!-- Resumen de respuestas (opcional) -->
      <div v-if="showAnswers" class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold text-gray-900">Resumen de Respuestas</h2>
          <button
            @click="showAnswers = false"
            class="text-sm text-gray-500 hover:text-gray-700"
          >
            Ocultar
          </button>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto">
          <div
            v-for="question in evaluation.questions"
            :key="question.id"
            class="border-b border-gray-200 pb-2"
          >
            <div class="flex justify-between">
              <span class="text-sm font-medium text-gray-700">
                {{ question.question }}
              </span>
              <span class="text-sm text-gray-900">
                {{ question.answer }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón para mostrar respuestas -->
      <div v-if="!showAnswers" class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="text-center">
          <button
            @click="showAnswers = true"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <EyeIcon class="h-4 w-4 mr-2" />
            Ver Resumen de Respuestas
          </button>
        </div>
      </div>

      <!-- Información adicional -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <div class="flex">
          <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5 mr-3" />
          <div>
            <h3 class="text-sm font-medium text-blue-800">Información Importante</h3>
            <div class="mt-2 text-sm text-blue-700">
              <ul class="list-disc list-inside space-y-1">
                <li>Tu evaluación ha sido guardada y procesada correctamente</li>
                <li>Los resultados serán analizados por el equipo correspondiente</li>
                <li>Guarda este número de folio para futuras referencias: <strong>{{ evaluation.folio }}</strong></li>
                <li>Si tienes preguntas, contacta al administrador del sistema</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="printResults"
            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PrinterIcon class="h-4 w-4 mr-2" />
            Imprimir Comprobante
          </button>
          <Link
            :href="route('evaluations.index')"
            class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <HomeIcon class="h-4 w-4 mr-2" />
            Volver al Inicio
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { 
  CheckCircleIcon, 
  EyeIcon, 
  InformationCircleIcon, 
  PrinterIcon, 
  HomeIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  evaluation: Object,
  title: String
})

const showAnswers = ref(false)

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const printResults = () => {
  window.print()
}
</script>

<style>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>