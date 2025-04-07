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
import { computed } from 'vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

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

// Generate dynamic colors for bars
const generateColors = (numColors) => {
  const colors = [];
  // Simple HSL-based color generation, adjust saturation/lightness as needed
  for (let i = 0; i < numColors; i++) {
    const hue = (i * (360 / numColors)) % 360;
    colors.push(`hsl(${hue}, 70%, 60%)`);
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
        // text: props.title // Optionally display title within chart
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
