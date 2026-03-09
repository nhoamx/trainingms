<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
      <p v-if="description" class="mt-1 text-sm text-gray-600">{{ description }}</p>
    </div>

    <div v-if="hasData" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
        <h4 class="mb-2 text-sm font-semibold text-gray-700">Dona</h4>
        <div class="h-72">
          <Pie :data="pieChartData" :options="pieChartOptions" />
        </div>
      </div>

      <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
        <h4 class="mb-2 text-sm font-semibold text-gray-700">Barras</h4>
        <div class="h-72">
          <Bar :data="barChartData" :options="barChartOptions" />
        </div>
      </div>
    </div>

    <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
      No hay respuestas suficientes para graficar.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Pie, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  type TooltipItem,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

interface DistributionItem {
  key: string;
  label: string;
  count: number;
  color: string;
}

const props = defineProps<{
  title: string;
  description?: string;
  items: DistributionItem[];
}>();

const hasData = computed(() => props.items.some((item) => item.count > 0));
const total = computed(() => props.items.reduce((sum, item) => sum + item.count, 0));

const pieChartData = computed(() => ({
  labels: props.items.map((item) => `${item.key} - ${item.label}`),
  datasets: [
    {
      data: props.items.map((item) => item.count),
      backgroundColor: props.items.map((item) => item.color),
      borderColor: '#ffffff',
      borderWidth: 2,
    },
  ],
}));

const barChartData = computed(() => ({
  labels: props.items.map((item) => `${item.key} - ${item.label}`),
  datasets: [
    {
      label: 'Total',
      data: props.items.map((item) => item.count),
      backgroundColor: props.items.map((item) => item.color),
      borderRadius: 6,
    },
  ],
}));

const pieChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        boxWidth: 14,
      },
    },
    tooltip: {
      callbacks: {
        label: (context: TooltipItem<'pie'>) => {
          const value = typeof context.parsed === 'number' ? context.parsed : 0;
          const percentage = total.value > 0 ? ((value / total.value) * 100).toFixed(1) : '0.0';
          return `${context.label ?? ''}: ${value} (${percentage}%)`;
        },
      },
    },
  },
}));

const barChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: (context: TooltipItem<'bar'>) => {
          const rawValue = context.parsed?.y;
          const value = typeof rawValue === 'number' ? rawValue : 0;
          const percentage = total.value > 0 ? ((value / total.value) * 100).toFixed(1) : '0.0';
          return `${value} (${percentage}%)`;
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
        text: 'Respuestas',
      },
    },
    x: {
      ticks: {
        maxRotation: 20,
        minRotation: 0,
      },
    },
  },
}));
</script>
