<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
      <p v-if="description" class="mt-1 text-sm text-gray-600">{{ description }}</p>
    </div>

    <div v-if="hasData" class="space-y-5">
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Vista:</span>
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
          :class="chartType === 'bar' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          @click="chartType = 'bar'"
        >
          Barras
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
          :class="chartType === 'doughnut' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          @click="chartType = 'doughnut'"
        >
          Dona
        </button>
      </div>

      <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
        <div class="h-72">
          <Bar v-if="chartType === 'bar'" :data="barChartData" :options="barChartOptions" />
          <Doughnut v-else :data="doughnutChartData" :options="doughnutChartOptions" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in items"
          :key="item.key"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }"></span>
              <p class="text-xs font-semibold text-gray-800">{{ item.key }} - {{ item.label }}</p>
            </div>
            <p class="text-xs font-semibold text-gray-700">{{ item.count }}</p>
          </div>
          <p class="mt-1 text-xs text-gray-500">{{ toPercentage(item.count) }}%</p>
        </div>
      </div>
    </div>

    <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
      No hay respuestas suficientes para graficar.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
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
const chartType = ref<'bar' | 'doughnut'>('bar');

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

const doughnutChartData = computed(() => ({
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
      grid: {
        display: false,
      },
      ticks: {
        maxRotation: 0,
        minRotation: 0,
      },
    },
  },
}));

const doughnutChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        boxWidth: 12,
        usePointStyle: true,
      },
    },
    tooltip: {
      callbacks: {
        label: (context: TooltipItem<'doughnut'>) => {
          const value = typeof context.parsed === 'number' ? context.parsed : 0;
          const percentage = total.value > 0 ? ((value / total.value) * 100).toFixed(1) : '0.0';
          return `${value} (${percentage}%)`;
        },
      },
    },
  },
}));

const toPercentage = (value: number): string => {
  if (total.value === 0) {
    return '0.0';
  }

  return ((value / total.value) * 100).toFixed(1);
};
</script>
