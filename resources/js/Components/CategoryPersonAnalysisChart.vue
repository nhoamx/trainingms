<script setup>
import { ref, computed } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  categoryData: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Colores para las opciones de respuesta
const colorMap = {
  'A': '#F44336', // Rojo - Muy Alto
  'B': '#FFB300', // Naranja - Alto
  'C': '#F8FF03', // Amarillo - Medio
  'D': '#8BC34A', // Verde - Bajo
  'E': '#4DD0C6'  // Turquesa - Nulo
};

// Etiquetas para las opciones de respuesta
const responseLabels = {
  'A': 'Siempre',
  'B': 'Casi siempre',
  'C': 'Algunas veces',
  'D': 'Casi nunca',
  'E': 'Nunca'
};

// Determinar si tenemos datos válidos para mostrar
const hasValidData = computed(() => {
  return props.categoryData && props.categoryData.length > 0;
});
</script>

<template>
  <div class="space-y-8">
    <!-- Gráficos individuales por categoría, 2 por fila -->
    <div v-if="hasValidData" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div 
        v-for="(category, index) in categoryData" 
        :key="`category-${category.id || index}`" 
        class="bg-white p-6 rounded-lg shadow"
      >
        <h3 class="text-lg font-semibold mb-4">{{ category.name }}</h3>
        
        <!-- Resumen numérico -->
        <div class="grid grid-cols-5 gap-2 mb-4">
          <div 
            v-for="(count, key) in category.responses"
            :key="`${category.id}-${key}`"
            class="p-2 rounded text-white flex flex-col items-center"
            :style="{ backgroundColor: colorMap[key] }"
          >
            <div class="text-xs mb-1">{{ key }} - {{ responseLabels[key] }}</div>
            <div class="text-lg font-bold">{{ count }}</div>
            <div class="text-xs">{{ (category.percentages[key] || 0).toFixed(1) }}%</div>
          </div>
        </div>
        
        <!-- Gráfico de barras para esta categoría -->
        <div class="h-64">
          <Bar 
            :data="{
              labels: Object.keys(category.responses).map(key => `${key} - ${responseLabels[key]}`),
              datasets: [{
                label: 'Personas',
                data: Object.values(category.responses),
                backgroundColor: Object.keys(category.responses).map(key => colorMap[key]),
                borderWidth: 1
              }]
            }"
            :options="{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const value = context.raw || 0;
                      const total = category.total || 0;
                      const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                      return `Personas: ${value} (${percentage}%)`;
                    }
                  }
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  title: {
                    display: true,
                    text: 'Número de Personas'
                  }
                }
              }
            }"
          />
        </div>
        
        <!-- Total -->
        <div class="mt-4 text-right">
          <span class="text-sm font-medium">Total de personas evaluadas: </span>
          <span class="text-sm font-bold">{{ category.total }}</span>
        </div>
      </div>
    </div>
    
    <div v-else class="text-center text-gray-500 py-8">
      No hay datos disponibles para mostrar.
    </div>
    
    <!-- Información general -->
    <div v-if="hasValidData" class="bg-white p-6 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-3">Análisis por Categoría</h3>
      <p class="text-sm text-gray-600">
        Los gráficos anteriores muestran el número de personas que han seleccionado cada opción de respuesta en al menos una pregunta
        dentro de cada categoría. Una misma persona puede contar en múltiples categorías si ha seleccionado diferentes opciones de respuesta.
      </p>
    </div>
  </div>
</template>
