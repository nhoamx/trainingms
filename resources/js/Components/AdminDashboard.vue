<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Organizaciones</h2>
      <div class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
        {{ organizations.length }} organizaciones
      </div>
    </div>

    <div v-if="organizations.length === 0" class="bg-white p-8 rounded-lg shadow text-center">
      <div class="text-gray-400 text-6xl mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
          class="w-16 h-16 mx-auto">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
      </div>
      <p class="text-gray-500 text-lg">No hay organizaciones registradas.</p>
      <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
        Crear organización
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="org in organizations" :key="org.id"
        class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden">
        <div class="p-5">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center bg-gray-100 rounded-full">
              <img v-if="org.logo" :src="org.logo" alt="Logo" class="h-10 w-10 object-contain" />
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-lg text-gray-900">{{ org.name }}</h3>
              <div class="flex items-center text-sm text-gray-500 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>{{ org.evaluations_count }} evaluaciones</span>
              </div>
              <p class="text-xs text-gray-400 mt-1">ID: {{ org.id.substring(0, 8) }}...</p>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="space-y-2">
            <!-- Botón para ver evaluaciones -->
            <a :href="route('organization.results.list', { organization: org.id })"
              class="w-full flex items-center justify-center text-green-600 hover:text-green-800 font-medium py-2 transition-colors border border-green-200 rounded-md hover:bg-green-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Ver Evaluaciones
            </a>

            <!-- Botón para reporte tradicional -->
            <a :href="route('organization.report', { id: org.id })"
              class="w-full flex items-center justify-center text-blue-600 hover:text-blue-800 font-medium py-2 transition-colors border border-blue-200 rounded-md hover:bg-blue-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Ver Reporte Tradicional
            </a>

            <!-- Botón para resultados en línea (solo si tiene quizzes online) -->
            <a v-if="org.online_quizzes_count > 0" :href="route('organization.online-results', { id: org.id })"
              class="w-full flex items-center justify-center text-green-600 hover:text-green-800 font-medium py-2 transition-colors border border-green-200 rounded-md hover:bg-green-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              Ver Resultados En Línea ({{ org.online_quizzes_count }})
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { AdjustmentsVerticalIcon } from '@heroicons/vue/24/outline';
import { defineProps, defineEmits } from 'vue';
const props = defineProps({
  organizations: {
    type: Array,
    default: () => []
  }
});
defineEmits(['see-report']);
</script>
