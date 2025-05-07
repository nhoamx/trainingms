<script setup>
import { computed } from 'vue';

const props = defineProps({
  categoryData: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Mapeo de respuestas a etiquetas y colores
const answerDetails = {
  'A': { label: 'Muy alto', bgColor: '#F44336', textColor: '#fff' },
  'B': { label: 'Alto', bgColor: '#FFB300', textColor: '#fff' },
  'C': { label: 'Medio', bgColor: '#FFC107', textColor: '#000' },
  'D': { label: 'Bajo', bgColor: '#8BC34A', textColor: '#fff' },
  'E': { label: 'Nulo', bgColor: '#4DD0C6', textColor: '#000' }
};
const atenderColor = { bgColor: '#B71C1C', textColor: '#fff' }; // Rojo marrón para "Atender"

// Ordenamos los tipos de respuesta para mostrarlos en la tabla
const answerTypes = ['E', 'D', 'C', 'B', 'A']; // Nulo, Bajo, Medio, Alto, Muy Alto

// Computar totales por tipo de respuesta y columna "Atender"
const columnTotals = computed(() => {
  const totals = {
    'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0, atender: 0 // Se eliminó total
  };
  
  props.categoryData.forEach(category => {
    answerTypes.forEach(type => {
      totals[type] += category.responses[type] || 0;
    });
    const atenderValue = (category.responses['A'] || 0) + (category.responses['B'] || 0);
    totals.atender += atenderValue;
    // Se eliminó la suma de category.total
  });
  
  return totals;
});

const getAtenderValue = (category) => {
  return (category.responses['A'] || 0) + (category.responses['B'] || 0);
};

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
            :style="{ backgroundColor: answerDetails[type].bgColor, color: answerDetails[type].textColor }"
          >
            {{ answerDetails[type].label }}
          </th>
          <th 
            scope="col" 
            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border"
            :style="{ backgroundColor: atenderColor.bgColor, color: atenderColor.textColor }"
          >
            Atender
          </th>
          <!-- Columna Total Respuestas eliminada del encabezado -->
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
            class="px-4 py-4 text-center text-sm border"
            :style="{ backgroundColor: answerDetails[type].bgColor, color: answerDetails[type].textColor }"
          >
            <div>{{ category.responses[type] || 0 }}</div>
          </td>
          <td 
            class="px-4 py-4 text-center text-sm border"
            :style="{ backgroundColor: atenderColor.bgColor, color: atenderColor.textColor }"
          >
            <div>{{ getAtenderValue(category) }}</div>
          </td>
          <!-- Celda category.total eliminada -->
        </tr>
        
        <!-- Fila de totales -->
        <tr class="bg-gray-100 font-bold">
          <td class="px-6 py-4 text-sm text-gray-900 border">
            TOTALES
          </td>
          <td 
            v-for="type in answerTypes" 
            :key="type" 
            class="px-4 py-4 text-center text-sm border"
            :style="{ backgroundColor: answerDetails[type].bgColor, color: answerDetails[type].textColor }"
          >
            <div>{{ columnTotals[type] }}</div>
          </td>
          <td 
            class="px-4 py-4 text-center text-sm border"
            :style="{ backgroundColor: atenderColor.bgColor, color: atenderColor.textColor }"
          >
            <div>{{ columnTotals.atender }}</div>
          </td>
          <!-- Celda columnTotals.total eliminada -->
        </tr>
      </tbody>
    </table>
  </div>
</template>
