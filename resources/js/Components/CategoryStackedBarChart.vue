<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    Colors
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Colors,
  ChartDataLabels
);

const props = defineProps({
  categoryData: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Define colores para cada tipo de respuesta
const answerColors = {
  'A': '#EF4444', // Rojo para "Siempre" (Muy Alto)
  'B': '#F97316', // Naranja para "Casi Siempre" (Alto)
  'C': '#F59E0B', // Amarillo para "Algunas Veces" (Medio)
  'D': '#10B981', // Verde para "Casi Nunca" (Bajo)
  'E': '#3B82F6', // Azul para "Nunca" (Nulo)
};

// Mapeo de respuestas a etiquetas
const answerLabels = {
  'A': 'Siempre',
  'B': 'Casi siempre',
  'C': 'Algunas veces', 
  'D': 'Casi nunca',
  'E': 'Nunca'
};

const processedChartData = computed(() => {
  // Si no hay datos, retornamos estructura vacía
  if (!props.categoryData || props.categoryData.length === 0) {
    return { labels: [], datasets: [] };
  }

  // Ordenamos los tipos de respuesta de E a A (de Nulo a Muy Alto)
  const answerTypes = ['E', 'D', 'C', 'B', 'A'];
  
  // Nombres de categorías para el eje X
  const labels = props.categoryData.map(category => category.name);
  
  // Crear un dataset para cada tipo de respuesta
  const datasets = answerTypes.map(answerType => {
    return {
      label: answerLabels[answerType] || `Respuesta ${answerType}`,
      data: props.categoryData.map(category => category.responses[answerType] || 0),
      backgroundColor: answerColors[answerType],
      borderColor: answerColors[answerType],
      borderWidth: 1,
    };
  });

  return { labels, datasets };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      stacked: true,
      title: {
        display: true,
        text: 'Categorías'
      }
    },
    y: {
      stacked: true,
      beginAtZero: true,
      title: {
        display: true,
        text: 'Número de Respuestas'
      }
    }
  },
  plugins: {
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: function(context) {
          let label = context.dataset.label || '';
          if (label) label += ': ';
          if (context.parsed.y !== null) {
            label += context.parsed.y;
            
            // Agregar porcentaje si es posible calcular
            const categoryIndex = context.dataIndex;
            const answerType = context.dataset.label.startsWith('Siempre') ? 'A' :
                              context.dataset.label.startsWith('Casi siempre') ? 'B' :
                              context.dataset.label.startsWith('Algunas veces') ? 'C' :
                              context.dataset.label.startsWith('Casi nunca') ? 'D' : 'E';
            
            if (props.categoryData[categoryIndex] && props.categoryData[categoryIndex].percentages) {
              const percentage = props.categoryData[categoryIndex].percentages[answerType];
              if (percentage !== undefined) {
                label += ` (${percentage.toFixed(1)}%)`;
              }
            }
          }
          return label;
        }
      }
    },
    legend: {
      position: 'top',
    },
    title: {
      display: true,
      text: 'Distribución de Respuestas por Categoría'
    },
    datalabels: {
      color: 'white',
      display: function(context) {
        return context.dataset.data[context.dataIndex] > 0; // Solo mostrar etiquetas para valores positivos
      },
      font: {
        weight: 'bold'
      },
      formatter: function(value, context) {
        // Solo mostrar valor si es significativo
        if (value < 5) return '';
        
        // Para valores mayores, mostrar el valor
        return value;
      }
    }
  }
}));

// Referencia al gráfico para posible interacción
const chartRef = ref(null);
</script>

<template>
  <div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="h-[500px]">
      <Bar
        v-if="processedChartData.labels.length > 0"
        :data="processedChartData"
        :options="chartOptions"
        ref="chartRef"
      />
      <div v-else class="h-full flex items-center justify-center text-gray-500">
        No hay datos disponibles para mostrar
      </div>
    </div>
  </div>
</template>
