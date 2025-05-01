<script setup>
import { computed } from 'vue';

const props = defineProps({
  domainData: {
    type: Array,
    required: true,
    default: () => []
  }
});

const typeLabels = {
  'A': 'Siempre',
  'B': 'Casi siempre',
  'C': 'Algunas veces',
  'D': 'Casi nunca',
  'E': 'Nunca',
};

// Crea un arreglo de tipos de respuesta ordenados para el encabezado de la tabla
const answerTypes = ['A', 'B', 'C', 'D', 'E'];

// Función para obtener el color de fondo según el tipo de respuesta
const getTypeColor = (type) => {
  switch (type) {
    case 'A': return '#F44336'; // Rojo - Muy Alto
    case 'B': return '#FFB300'; // Naranja - Alto
    case 'C': return '#FFEB3B'; // Amarillo - Medio
    case 'D': return '#8BC34A'; // Verde - Bajo
    case 'E': return '#4DD0C6'; // Turquesa - Nulo
    default: return '#E5E7EB'; // Gris por defecto
  }
};

// Función para obtener el color de texto según el tipo de respuesta
const getTextColor = (type) => {
  return ['C', 'E'].includes(type) ? '#000000' : '#FFFFFF';
};
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Dominio
            </th>
            <th 
              v-for="type in answerTypes" 
              :key="type" 
              scope="col" 
              class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider"
              :style="{ backgroundColor: getTypeColor(type), color: getTextColor(type), opacity: 0.8 }"
            >
              {{ typeLabels[type] }}
            </th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              Total
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(domain, index) in domainData" :key="domain.id" 
              :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ domain.name }}
            </td>
            <td v-for="type in answerTypes" :key="`${domain.id}-${type}`" class="px-4 py-4 whitespace-nowrap text-sm text-center">
              <div>
                <span class="font-semibold">{{ domain.responses[type] || 0 }}</span>
                <div class="text-xs text-gray-500">{{ domain.percentages && domain.percentages[type] ? `${domain.percentages[type].toFixed(1)}%` : '0.0%' }}</div>
                <!-- Barra de porcentaje -->
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                  <div 
                    class="h-1.5 rounded-full"
                    :style="{
                      width: `${domain.percentages && domain.percentages[type] ? domain.percentages[type] : 0}%`,
                      backgroundColor: getTypeColor(type)
                    }"
                  ></div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
              {{ domain.total || 0 }}
            </td>
          </tr>
          <!-- Fila de totales -->
          <tr class="bg-gray-100 font-semibold">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
              TOTALES
            </td>
            <td v-for="type in answerTypes" :key="`total-${type}`" class="px-4 py-4 whitespace-nowrap text-sm text-center">
              {{ props.domainData.reduce((sum, domain) => sum + (domain.responses[type] || 0), 0) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
              {{ props.domainData.reduce((sum, domain) => sum + (domain.total || 0), 0) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
