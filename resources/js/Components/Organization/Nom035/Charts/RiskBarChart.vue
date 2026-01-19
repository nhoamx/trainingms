<template>
  <div class="space-y-4">
    <h4 class="text-sm font-semibold text-slate-700">
      {{ title }}
    </h4>
    <div class="relative">
      <canvas ref="chartCanvas" class="max-h-80"></canvas>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';
import { Chart, BarElement, CategoryScale, LinearScale, Tooltip, Legend, type ChartConfiguration } from 'chart.js';

// Register Chart.js components
Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

interface Props {
  distribution: Record<string, number>;
  colors: Record<string, string>;
  labels: Record<string, string>;
  title: string;
}

const props = defineProps<Props>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

// Orden de severidad
const orderedLevels = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

const createChart = () => {
  if (!chartCanvas.value) return;

  // Mostrar todos los niveles, incluso si tienen 0
  const chartLabels = orderedLevels.map(level => props.labels[level]);
  const chartData = orderedLevels.map(level => props.distribution[level] || 0);
  const backgroundColors = orderedLevels.map(level => props.colors[level]);

  const total = chartData.reduce((sum, value) => sum + value, 0);

  const config: ChartConfiguration<'bar'> = {
    type: 'bar',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Número de Evaluaciones',
        data: chartData,
        backgroundColor: backgroundColors,
        borderWidth: 2,
        borderColor: backgroundColors.map(color => color),
        borderRadius: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
            font: {
              size: 11,
            },
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
          },
        },
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 11,
            },
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const value = context.parsed.y as number;
              const percentage = ((value / total) * 100).toFixed(1);
              return `${value} personas (${percentage}%)`;
            },
          },
        },
      },
    },
  };

  // Destroy previous chart if exists
  if (chartInstance) {
    chartInstance.destroy();
  }

  // Create new chart
  chartInstance = new Chart(chartCanvas.value, config);
};

const updateChart = () => {
  nextTick(() => {
    createChart();
  });
};

onMounted(() => {
  createChart();
});

watch(() => props.distribution, () => {
  updateChart();
}, { deep: true });

watch(() => props.title, () => {
  updateChart();
});
</script>
