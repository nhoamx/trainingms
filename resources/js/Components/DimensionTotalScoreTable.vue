<script setup>
import { computed } from 'vue';

const props = defineProps({
  dimensionScores: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Ordenamos los resultados por puntuación total de mayor a menor
const sortedScores = computed(() => {
  return [...props.dimensionScores].sort((a, b) => b.total_score - a.total_score);
});

// Función para obtener el color de riesgo basado en la puntuación
const getRiskColor = (score) => {
  // Calculamos el color basado en la puntuación relativa al máximo
  const maxScore = Math.max(...sortedScores.value.map(s => s.total_score));
  const normalizedScore = maxScore > 0 ? score / maxScore : 0;
  
  // Esquema de colores: rojo (alto riesgo) a verde (bajo riesgo)
  if (normalizedScore > 0.8) return '#F44336'; // Rojo - Muy Alto
  if (normalizedScore > 0.6) return '#FFB300'; // Naranja - Alto
  if (normalizedScore > 0.4) return '#FFEB3B'; // Amarillo - Medio
  if (normalizedScore > 0.2) return '#8BC34A'; // Verde - Bajo
  return '#4DD0C6'; // Turquesa - Nulo
};

// Función para obtener el nivel de riesgo basado en la puntuación
const getRiskLevel = (score) => {
  // Calculamos el color basado en la puntuación relativa al máximo
  const maxScore = Math.max(...sortedScores.value.map(s => s.total_score));
  const normalizedScore = maxScore > 0 ? score / maxScore : 0;
  
  if (normalizedScore > 0.8) return 'Muy Alto';
  if (normalizedScore > 0.6) return 'Alto';
  if (normalizedScore > 0.4) return 'Medio';
  if (normalizedScore > 0.2) return 'Bajo';
  return 'Nulo';
};

// Función para obtener el color de texto según el color de fondo
const getTextColor = (backgroundColor) => {
  return backgroundColor === '#FFEB3B' || backgroundColor === '#4DD0C6' ? '#000000' : '#FFFFFF';
};
</script>

<template>
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Posición
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Dimensión
            </th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              Puntuación Total
            </th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              Núm. Preguntas
            </th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              Promedio
            </th>
            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              Nivel de Riesgo
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(dimension, index) in sortedScores" :key="dimension.id" 
              :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center">
              {{ index + 1 }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ dimension.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold">
              {{ dimension.total_score }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              {{ dimension.question_count }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              {{ dimension.avg_score }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              <span 
                class="px-3 py-1 rounded-full font-semibold"
                :style="{
                  backgroundColor: getRiskColor(dimension.total_score),
                  color: getTextColor(getRiskColor(dimension.total_score))
                }"
              >
                {{ getRiskLevel(dimension.total_score) }}
              </span>
            </td>
          </tr>
          <!-- Fila de totales -->
          <tr class="bg-gray-100 font-semibold">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              -
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
              TOTALES
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
              {{ sortedScores.reduce((sum, dim) => sum + dim.total_score, 0) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              {{ sortedScores.reduce((sum, dim) => sum + dim.question_count, 0) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              {{ (sortedScores.reduce((sum, dim) => sum + dim.total_score, 0) / 
                 sortedScores.reduce((sum, dim) => sum + dim.question_count, 0)).toFixed(2) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
              -
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
