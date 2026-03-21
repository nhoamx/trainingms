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
import { Chart, ArcElement, Tooltip, Legend, type ChartConfiguration } from 'chart.js';

// Register Chart.js components
Chart.register(ArcElement, Tooltip, Legend);

interface Props {
  distribution: Record<string, number>;
  colors: Record<string, string>;
  labels: Record<string, string>;
  title: string;
  variant?: 'pie' | 'doughnut';
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
  const getPercentage = (value: number): string => {
    if (total === 0) {
      return '0.0';
    }

    return ((value / total) * 100).toFixed(1);
  };

  const chartVariant = props.variant ?? 'pie';

  const config: ChartConfiguration<'pie' | 'doughnut'> = {
    type: chartVariant,
    data: {
      labels: chartLabels,
      datasets: [{
        data: chartData,
        backgroundColor: backgroundColors,
        borderWidth: 2,
        borderColor: '#ffffff',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            font: {
              size: 12,
            },
            generateLabels: (chart) => {
              const data = chart.data;
              if (data.labels && data.datasets.length) {
                return data.labels.map((label, i) => {
                  const value = data.datasets[0].data[i] as number;
                  const percentage = getPercentage(value);
                  return {
                    text: `${label}: ${value} (${percentage}%)`,
                    fillStyle: backgroundColors[i],
                    hidden: false,
                    index: i,
                  };
                });
              }
              return [];
            },
          },
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed as number;
              const percentage = getPercentage(value);
              return `${label}: ${value} personas (${percentage}%)`;
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

  chartInstance = new Chart(chartCanvas.value, config);
};

onMounted(() => {
  nextTick(() => {
    createChart();
  });
});

// Recreate chart when distribution changes
watch(() => props.distribution, () => {
  nextTick(() => {
    createChart();
  });
}, { deep: true });

watch(() => props.title, () => {
  nextTick(() => {
    createChart();
  });
});

watch(() => props.variant, () => {
  nextTick(() => {
    createChart();
  });
});
</script>
