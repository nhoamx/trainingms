<script setup>
import { computed } from 'vue';

const props = defineProps({
  domainData: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Etiquetas y colores igual que en CategoryResponseTable
const answerDetails = {
  'A': { label: 'Muy alto', bgColor: '#F44336', textColor: '#fff' },
  'B': { label: 'Alto', bgColor: '#FFB300', textColor: '#fff' },
  'C': { label: 'Medio', bgColor: '#F8FF03', textColor: '#000' },
  'D': { label: 'Bajo', bgColor: '#8BC34A', textColor: '#fff' },
  'E': { label: 'Nulo', bgColor: '#4DD0C6', textColor: '#000' }
};
const atenderColor = { bgColor: '#B71C1C', textColor: '#fff' };

const answerTypes = ['E', 'D', 'C', 'B', 'A']; // Nulo, Bajo, Medio, Alto, Muy Alto

// Totales por tipo y atender
const columnTotals = computed(() => {
  const totals = { 'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0, atender: 0 };
  props.domainData.forEach(domain => {
    answerTypes.forEach(type => {
      totals[type] += domain.responses[type] || 0;
    });
    const atenderValue = (domain.responses['A'] || 0) + (domain.responses['B'] || 0);
    totals.atender += atenderValue;
  });
  return totals;
});
const getAtenderValue = (domain) => (domain.responses['A'] || 0) + (domain.responses['B'] || 0);
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
              class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border"
              :style="{ backgroundColor: answerDetails[type].bgColor, color: answerDetails[type].textColor }"
            >
              {{ answerDetails[type].label }}
            </th>
            <th 
              scope="col" 
              class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border"
              :style="{ backgroundColor: atenderColor.bgColor, color: atenderColor.textColor }"
            >
              Atender
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(domain, index) in domainData" :key="domain.id" 
              :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ domain.name }}
            </td>
            <td 
              v-for="type in answerTypes" 
              :key="`${domain.id}-${type}`" 
              class="px-4 py-4 text-center text-sm border"
              :style="{ backgroundColor: answerDetails[type].bgColor, color: answerDetails[type].textColor }"
            >
              <div>{{ domain.responses[type] || 0 }}</div>
            </td>
            <td 
              class="px-4 py-4 text-center text-sm border"
              :style="{ backgroundColor: atenderColor.bgColor, color: atenderColor.textColor }"
            >
              <div>{{ getAtenderValue(domain) }}</div>
            </td>
          </tr>
          <!-- Fila de totales -->
          <tr class="bg-gray-100 font-bold">
            <td class="px-6 py-4 text-sm text-gray-900 border">
              TOTALES
            </td>
            <td 
              v-for="type in answerTypes" 
              :key="`total-${type}`" 
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
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
