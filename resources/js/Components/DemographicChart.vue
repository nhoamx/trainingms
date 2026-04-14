<script setup>
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import ChartDataLabels from 'chartjs-plugin-datalabels'
import { computed } from 'vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ChartDataLabels)

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  chartData: {
    type: Array, // Expects array of { label: string, count: number }
    required: true
  }
})

// Generate dynamic colors for bars - usando colores consistentes del sistema
const generateColors = (numColors) => {
  // Colores base del sistema de evaluación para consistencia
  const baseColors = [
    '#00CED1', // Turquesa claro
    '#28A745', // Verde césped
    '#FFFF00', // Amarillo brillante
    '#FFA500', // Naranja
    '#FF0000', // Rojo
    '#6366F1', // Índigo
    '#8B5CF6', // Violet
    '#EC4899', // Pink
    '#10B981', // Emerald
    '#F8FF03', // Amber
    '#EF4444', // Red-500
    '#3B82F6'  // Blue-500
  ];
  
  const colors = [];
  for (let i = 0; i < numColors; i++) {
    colors.push(baseColors[i % baseColors.length]);
  }
  return colors;
}

const processedChartData = computed(() => {
  if (!props.chartData || props.chartData.length === 0) {
    return { labels: [], datasets: [] };
  }

  const labels = props.chartData.map(item => item.label);
  const data = props.chartData.map(item => item.count);
  const backgroundColors = generateColors(labels.length);

  return {
    labels: labels,
    datasets: [
      {
        label: props.title, // Or keep it simple like 'Count'
        backgroundColor: backgroundColors,
        borderColor: backgroundColors.map(color => color.replace('60%', '50%')), // Slightly darker border
        borderWidth: 1,
        data: data
      }
    ]
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false // Hide legend for cleaner look with many potential bars
    },
    title: {
        display: false, // Title is already above the chart
    },
    tooltip: {
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                     // Show the category label and its count
                    label += `${context.label}: ${context.parsed.y}`;
                }
                return label;
            }
        }
    },
    // Plugin para mostrar valores en las barras con texto dinámico
    datalabels: {
      display: true,
      color: function(context) {
        // Obtener el color de fondo de la barra
        const backgroundColor = context.dataset.backgroundColor[context.dataIndex];
        
        // Colores claros que requieren texto negro
        const lightColors = ['#FFFF00', '#00CED1', '#F8FF03', '#FBBF24', '#FEF08A', '#FED7AA'];
        
        // Si el color está en la lista de colores claros, usar texto negro
        if (lightColors.includes(backgroundColor)) {
          return '#000000';
        }
        
        // Para colores oscuros, usar texto blanco
        return '#FFFFFF';
      },
      font: {
        weight: 'bold',
        size: 12
      },
      formatter: function(value) {
        return value > 0 ? value : ''; // Solo mostrar si hay valor
      },
      anchor: 'center',
      align: 'center'
    }
  },
  scales: {
    y: {
      beginAtZero: true,
       ticks: {
            // Ensure only integers are shown on the Y axis
            precision: 0
        }
    }
  }
}))

</script>

<template>
  <div class="chart-container" style="position: relative; height:300px; width:100%">
    <Bar
      v-if="processedChartData.labels.length > 0"
      :options="chartOptions"
      :data="processedChartData"
    />
     <p v-else class="text-center text-gray-500 pt-10">No hay datos para graficar.</p>
  </div>
</template>

<style scoped>
/* Add any specific styling if needed */
</style>
