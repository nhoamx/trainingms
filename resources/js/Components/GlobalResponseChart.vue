<script setup>
import { ref, computed } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
import { Pie, Bar } from 'vue-chartjs';

ChartJS.register(ArcElement, CategoryScale, LinearScale, BarElement, Tooltip, Legend, Title);

const props = defineProps({
  responseData: {
    type: Object,
    required: true,
    default: () => ({})
  }
});

// Determinar si tenemos datos válidos para mostrar
const hasValidData = computed(() => {
  return props.responseData && 
         props.responseData.response_counts && 
         Object.keys(props.responseData.response_counts).length > 0;
});

// Preparar los datos para el gráfico de pastel
const pieChartData = computed(() => {
  if (!hasValidData.value) return { labels: [], datasets: [] };
  
  // Obtener los datos de respuesta
  const responseData = props.responseData.response_counts;
  
  // Preparar etiquetas y datos
  const labels = Object.keys(responseData).map(key => {
    return responseData[key].label;
  });
  
  const data = Object.keys(responseData).map(key => {
    return responseData[key].value;
  });
  
  const backgroundColor = Object.keys(responseData).map(key => {
    return responseData[key].color;
  });
  
  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor,
        borderWidth: 1
      }
    ]
  };
});

// Preparar los datos para el gráfico de barras
const barChartData = computed(() => {
  if (!hasValidData.value) return { labels: [], datasets: [] };
  
  // Obtener los datos de respuesta
  const responseData = props.responseData.response_counts;
  
  // Preparar etiquetas y datos
  const labels = Object.keys(responseData).map(key => {
    return key + ' - ' + responseData[key].label;
  });
  
  const data = Object.keys(responseData).map(key => {
    return responseData[key].value;
  });
  
  const backgroundColor = Object.keys(responseData).map(key => {
    return responseData[key].color;
  });
  
  return {
    labels,
    datasets: [
      {
        label: 'Total de Respuestas',
        data,
        backgroundColor,
        borderWidth: 1
      }
    ]
  };
});

// Opciones para el gráfico de pastel
const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
    },
    title: {
      display: true,
      text: 'Distribución Global de Respuestas'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          let label = context.label || '';
          if (label) {
            label += ': ';
          }
          if (context.parsed !== undefined) {
            const value = context.parsed;
            const percentage = ((value / props.responseData.total_responses) * 100).toFixed(2);
            label += value + ' (' + percentage + '%)';
          }
          return label;
        }
      }
    }
  }
};

// Opciones para el gráfico de barras
const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Total de Respuestas'
      }
    },
    x: {
      title: {
        display: true,
        text: 'Opción de Respuesta'
      }
    }
  },
  plugins: {
    legend: {
      display: false
    },
    title: {
      display: true,
      text: 'Distribución Global de Respuestas'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const value = context.parsed.y;
          const percentage = ((value / props.responseData.total_responses) * 100).toFixed(2);
          return 'Total: ' + value + ' (' + percentage + '%)';
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
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Gráfico de pastel -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-lg font-semibold mb-4">Gráfico de Pastel</h3>
          <div class="h-80">
            <Pie 
              :data="pieChartData"
              :options="pieChartOptions"
            />
          </div>
        </div>
        
        <!-- Gráfico de barras -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-lg font-semibold mb-4">Gráfico de Barras</h3>
          <div class="h-80">
            <Bar 
              :data="barChartData"
              :options="barChartOptions"
            />
          </div>
        </div>
      </div>
      
      <!-- Tabla de resumen -->
      <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold mb-4">Resumen de Respuestas</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Opción
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Descripción
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Porcentaje
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(data, key) in props.responseData.response_counts" :key="key">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ key }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ data.label }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium">
                  {{ data.value }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                  {{ data.percentage.toFixed(2) }}%
                </td>
              </tr>
              <!-- Fila de totales -->
              <tr class="bg-gray-50">
                <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  Total
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  {{ props.responseData.total_responses }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                  100%
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
          Este reporte muestra la distribución global de las respuestas en todo el cuestionario de la NOM-035-STPS-2018 (Guía III).
          Permite visualizar la tendencia general en el estilo de respuesta de los participantes.
        </p>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
          <div class="flex">
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Las opciones con mayor frecuencia indican las percepciones predominantes entre los trabajadores evaluados.
                Una alta frecuencia de respuestas A ("Siempre") y B ("Casi siempre") podría indicar un mayor nivel de riesgo psicosocial percibido.
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
