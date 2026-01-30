<template>
  <div class="bg-white rounded-lg border border-slate-200 p-6">
    <h4 class="text-lg font-semibold text-slate-900 mb-4">{{ title }}</h4>
    <div class="h-80">
      <canvas ref="canvasRef"></canvas>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface Props {
  title: string;
  data: { label: string; count: number }[];
}

const props = defineProps<Props>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const chart = ref<Chart | null>(null);

const createChart = () => {
  if (!canvasRef.value) return;

  // Destruir gráfico anterior si existe
  if (chart.value) {
    chart.value.destroy();
  }

  const ctx = canvasRef.value.getContext('2d');
  if (!ctx) return;

  const labels = props.data.map(item => item.label || 'Sin especificar');
  const values = props.data.map(item => item.count);

  // Colores vibrantes para las barras
  const colors = [
    '#3B82F6', // blue-500
    '#10B981', // green-500
    '#F59E0B', // amber-500
    '#EF4444', // red-500
    '#8B5CF6', // violet-500
    '#EC4899', // pink-500
    '#14B8A6', // teal-500
    '#F97316', // orange-500
    '#6366F1', // indigo-500
    '#84CC16', // lime-500
  ];

  chart.value = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Evaluaciones',
        data: values,
        backgroundColor: colors.slice(0, values.length),
        borderColor: colors.slice(0, values.length),
        borderWidth: 1,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const value = context.raw as number;
              const total = values.reduce((acc, val) => acc + val, 0);
              const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
              return `${value} evaluaciones (${percentage}%)`;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
          title: {
            display: true,
            text: 'Número de evaluaciones',
          },
        },
        x: {
          ticks: {
            autoSkip: false,
            maxRotation: 45,
            minRotation: 45,
          },
        },
      },
    },
  });
};

onMounted(() => {
  createChart();
});

watch(() => props.data, () => {
  createChart();
}, { deep: true });

onBeforeUnmount(() => {
  if (chart.value) {
    chart.value.destroy();
  }
});
</script>
