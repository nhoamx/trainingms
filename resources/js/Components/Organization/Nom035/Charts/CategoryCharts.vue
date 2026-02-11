<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-xl font-bold text-slate-900">Análisis por Categoría</h3>
      <p class="text-sm text-slate-600">{{ totalEvaluations }} evaluaciones analizadas</p>
    </div>

    <!-- Gráficas por categoría en grid de 2 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div v-for="(category, categoryName) in categories" :key="categoryName" class="bg-white rounded-lg p-6 border border-slate-200">
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-base font-semibold text-slate-900">{{ categoryName }}</h4>
            <!-- <span class="px-2 py-1 rounded-full text-xs font-medium" :style="{ backgroundColor: getRiskLevelBgColor(category.risk_level), color: getRiskLevelTextColor(category.risk_level) }">
              {{ category.risk_level_label }}
            </span> -->
          </div>
          <p class="text-sm text-slate-600">
            Promedio: <strong class="text-slate-900">{{ category.average_score }}</strong> / {{ category.max_score }} ({{ category.percentage }}%)
          </p>
        </div>
        
        <canvas :ref="el => categoryChartRefs[categoryName] = el" style="height: 200px"></canvas>
        
        <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
          <div v-for="(count, level) in category.distribution" :key="level" v-show="count > 0" class="flex justify-between items-center">
            <div class="flex items-center gap-1.5">
              <div class="w-2.5 h-2.5 rounded" :style="{ backgroundColor: colors[level] }"></div>
              <span class="text-slate-700">{{ labels[level] }}</span>
            </div>
            <span class="font-semibold text-slate-900">{{ count }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface CategoryData {
  average_score: number;
  max_score: number;
  percentage: number;
  risk_level: string;
  risk_level_label: string;
  distribution: Record<string, number>;
  total_evaluations: number;
  // CORREGIDO: Las categorías no tienen padre (son el primer nivel)
}

interface Props {
  categories: Record<string, CategoryData>;
  totalEvaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

const props = defineProps<Props>();

const categoryChartRefs = ref<Record<string, HTMLCanvasElement | null>>({});
const chartInstances = ref<Record<string, Chart>>({});

// Helper para obtener color con opacidad
const getLevelColor = (level: string, opacity: number): string => {
  const color = props.colors[level];
  if (!color) return `rgba(148, 163, 184, ${opacity})`;
  
  // Convertir hex a rgba
  const hex = color.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

const getRiskLevelBgColor = (level: string): string => {
  return getLevelColor(level, 0.15);
};

const getRiskLevelTextColor = (level: string): string => {
  return props.colors[level] || '#64748b';
};

const getConsistentStepSize = (maxValue: number): number => {
  if (maxValue <= 10) return 1;
  if (maxValue <= 50) return 5;
  if (maxValue <= 100) return 10;
  if (maxValue <= 500) return 50;
  return Math.ceil(maxValue / 10);
};

const createCategoryChart = (canvasRef: HTMLCanvasElement, categoryName: string, category: CategoryData): void => {
  if (!canvasRef) return;

  const ctx = canvasRef.getContext('2d');
  if (!ctx) return;

  const chartKey = `category_${categoryName}`;
  const existingChart = chartInstances.value[chartKey];
  if (existingChart) {
    existingChart.destroy();
  }

  // Filtrar y ordenar niveles con datos
  const levelsOrder = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  const labels = levelsOrder.filter(level => category.distribution[level] > 0);
  const data = labels.map(level => category.distribution[level]);
  const backgroundColors = labels.map(level => props.colors[level]);

  const maxValue = Math.max(...data, 0);
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.map(level => props.labels[level]),
      datasets: [
        {
          data,
          backgroundColor: backgroundColors,
          borderColor: backgroundColors,
          borderWidth: 2,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      indexAxis: 'x',
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.parsed.y} evaluaciones`;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize,
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
    },
  });

  chartInstances.value[chartKey] = chart;
};

const renderCharts = (): void => {
  nextTick(() => {
    // Crear gráficas individuales por categoría
    Object.entries(props.categories).forEach(([categoryName, category]) => {
      const canvasRef = categoryChartRefs.value[categoryName];
      if (canvasRef) {
        createCategoryChart(canvasRef, categoryName, category);
      }
    });
  });
};

watch(() => props.categories, () => {
  renderCharts();
}, { deep: true });

onMounted(() => {
  renderCharts();
});
</script>
