<script setup>
import { ref, computed } from 'vue';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  categoryData: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Determinar si tenemos datos válidos para mostrar
const hasValidData = computed(() => {
  return props.categoryData && props.categoryData.length > 0;
});

// Colores para cada tipo de respuesta
const answerColors = {
  'A': '#F44336', // Rojo - Siempre
  'B': '#FFB300', // Naranja - Casi siempre
  'C': '#FFEB3B ', // Amarillo - Algunas veces 
  'D': '#8BC34A', // Verde - Casi nunca
  'E': '#4DD0C6', // Turquesa - Nunca
};

// Etiquetas para cada tipo de respuesta
const answerLabels = {
  'A': 'Siempre',
  'B': 'Casi siempre',
  'C': 'Algunas veces',
  'D': 'Casi nunca',
  'E': 'Nunca',
};

// Preparar los datos para el gráfico de barras agrupadas
const groupedBarChartData = computed(() => {
  if (!hasValidData.value) return { labels: [], datasets: [] };
  
  // Obtener las categorías para las etiquetas del eje X
  const categories = props.categoryData.map(cat => cat.name);
  
  // Crear un dataset para cada tipo de respuesta (A, B, C, D, E)
  const datasets = ['A', 'B', 'C', 'D', 'E'].map(answerType => {
    return {
      label: answerLabels[answerType],
      backgroundColor: answerColors[answerType],
      data: props.categoryData.map(cat => cat.responses[answerType] || 0)
    };
  });
  
  return {
    labels: categories,
    datasets
  };
});

// Opciones para el gráfico de barras agrupadas
const groupedBarChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      title: {
        display: true,
        text: 'Categorías'
      }
    },
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Número de Respuestas'
      }
    }
  },
  plugins: {
    title: {
      display: true,
      text: 'Distribución de Respuestas por Categoría'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const label = context.dataset.label || '';
          const value = context.parsed.y;
          const categoryIndex = context.dataIndex;
          const category = props.categoryData[categoryIndex];
          const total = category.total;
          const percentage = ((value / total) * 100).toFixed(2);
          return label + ': ' + value + ' (' + percentage + '%)';
        }
      }
    }
  }
};
</script>

<template>
  <div>
    <div v-if="!hasValidData" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
      No hay datos disponibles para mostrar.
    </div>
    
    <div v-else class="space-y-8">
      <!-- Gráfico de barras agrupadas -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold mb-4">Distribución de Respuestas por Categoría y Opción</h3>
        <div class="h-96">
          <Bar 
            :data="groupedBarChartData"
            :options="groupedBarChartOptions"
          />
        </div>
      </div>
      
      <!-- Tabla de doble entrada -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold mb-4">Tabla de Respuestas por Categoría</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Categoría
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  A<br><span class="text-xs font-normal">(Siempre)</span>
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  B<br><span class="text-xs font-normal">(Casi siempre)</span>
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  C<br><span class="text-xs font-normal">(Algunas veces)</span>
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  D<br><span class="text-xs font-normal">(Casi nunca)</span>
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  E<br><span class="text-xs font-normal">(Nunca)</span>
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(category, index) in props.categoryData" :key="category.id" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ category.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center" 
                    :style="{backgroundColor: category.responses.A > 0 ? 'rgba(244, 67, 54, 0.1)' : ''}">
                  <div>{{ category.responses.A }}</div>
                  <div class="text-xs text-gray-500">{{ category.percentages.A.toFixed(1) }}%</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center"
                    :style="{backgroundColor: category.responses.B > 0 ? 'rgba(255, 179, 0, 0.1)' : ''}">
                  <div>{{ category.responses.B }}</div>
                  <div class="text-xs text-gray-500">{{ category.percentages.B.toFixed(1) }}%</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center"
                    :style="{backgroundColor: category.responses.C > 0 ? 'rgba(255, 235, 59, 0.1)' : ''}">
                  <div>{{ category.responses.C }}</div>
                  <div class="text-xs text-gray-500">{{ category.percentages.C.toFixed(1) }}%</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center"
                    :style="{backgroundColor: category.responses.D > 0 ? 'rgba(139, 195, 74, 0.1)' : ''}">
                  <div>{{ category.responses.D }}</div>
                  <div class="text-xs text-gray-500">{{ category.percentages.D.toFixed(1) }}%</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center"
                    :style="{backgroundColor: category.responses.E > 0 ? 'rgba(77, 208, 198, 0.1)' : ''}">
                  <div>{{ category.responses.E }}</div>
                  <div class="text-xs text-gray-500">{{ category.percentages.E.toFixed(1) }}%</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ category.total }}
                </td>
              </tr>
              <!-- Fila de totales -->
              <tr class="bg-gray-100">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                  TOTALES
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.categoryData.reduce((sum, cat) => sum + cat.responses.A, 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.categoryData.reduce((sum, cat) => sum + cat.responses.B, 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.categoryData.reduce((sum, cat) => sum + cat.responses.C, 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.categoryData.reduce((sum, cat) => sum + cat.responses.D, 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.categoryData.reduce((sum, cat) => sum + cat.responses.E, 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.categoryData.reduce((sum, cat) => sum + cat.total, 0) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Interpretación -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold mb-3">Interpretación</h3>
        <p class="text-sm text-gray-600 mb-4">
          Este reporte muestra la distribución de respuestas por categoría y opción en el cuestionario de la NOM-035-STPS-2018 (Guía III).
          Permite visualizar qué categorías tienen mayores concentraciones de respuestas específicas, lo que ayuda a identificar áreas de riesgo psicosocial.
        </p>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
          <div class="flex">
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Las categorías con mayor concentración de respuestas A ("Siempre") y B ("Casi siempre") podrían requerir mayor atención,
                pues representan factores de riesgo psicosocial percibidos con mayor frecuencia por los trabajadores.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
