<script setup>
import { computed } from 'vue';

const props = defineProps({
  categoryData: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Mapeo de respuestas a etiquetas
const answerLabels = {
  'A': 'Siempre',
  'B': 'Casi siempre',
  'C': 'Algunas veces', 
  'D': 'Casi nunca',
  'E': 'Nunca'
};

// Ordenamos los tipos de respuesta para mostrarlos en la tabla
const answerTypes = ['E', 'D', 'C', 'B', 'A'];

// Computar totales por tipo de respuesta
const columnTotals = computed(() => {
  const totals = {
    'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0, 'total': 0
  };
  
  props.categoryData.forEach(category => {
    answerTypes.forEach(type => {
      totals[type] += category.responses[type] || 0;
    });
    totals.total += category.total || 0;
  });
  
  return totals;
});
</script>

<template>
  <div class="bg-white p-6 rounded-lg shadow-lg mt-6 overflow-x-auto">
    <h3 class="text-lg font-semibold mb-4">Resumen de Respuestas por Categoría</h3>
    
    <table class="min-w-full divide-y divide-gray-200 border">
      <thead class="bg-gray-50">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border">
            Categoría
          </th>
          <th 
            v-for="type in answerTypes" 
            :key="type" 
            scope="col" 
            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border"
          >
            {{ answerLabels[type] || type }}
          </th>
          <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">
            Total
          </th>
        </tr>
      </thead>
      
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="(category, index) in categoryData" :key="index" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
          <td class="px-6 py-4 text-sm font-medium text-gray-900 border">
            {{ category.name }}
          </td>
          <td 
            v-for="type in answerTypes" 
            :key="type" 
            class="px-4 py-4 text-center text-sm text-gray-900 border"
          >
            <div>{{ category.responses[type] || 0 }}</div>
            <div class="text-xs text-gray-500">{{ category.percentages && category.percentages[type] ? `${category.percentages[type].toFixed(1)}%` : '0.0%' }}</div>
          </td>
          <td class="px-4 py-4 text-center text-sm font-bold text-gray-900 border">
            {{ category.total }}
          </td>
        </tr>
        
        <!-- Fila de totales -->
        <tr class="bg-gray-100 font-bold">
          <td class="px-6 py-4 text-sm text-gray-900 border">
            TOTALES
          </td>
          <td 
            v-for="type in answerTypes" 
            :key="type" 
            class="px-4 py-4 text-center text-sm text-gray-900 border"
          >
            <div>{{ columnTotals[type] }}</div>
            <div class="text-xs text-gray-600">
              {{ columnTotals.total > 0 ? `${((columnTotals[type] / columnTotals.total) * 100).toFixed(1)}%` : '0.0%' }}
            </div>
          </td>
          <td class="px-4 py-4 text-center text-sm text-gray-900 border">
            {{ columnTotals.total }}
          </td>
        </tr>
      </tbody>
    </table>
    
    <div class="mt-4 text-sm text-gray-500">
      <p>* Los porcentajes se calculan sobre el total de respuestas por categoría</p>
    </div>
  </div>
</template>
