<template>
  <div v-if="dimensionStatistics && dimensionStatistics.dimensions" class="space-y-6">
    <!-- Gráficas por dimensión en grid de 2 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div v-for="(dimension, dimensionName) in dimensions" :key="dimensionName" class="bg-white rounded-lg p-6 border border-slate-200">
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-semibold text-slate-900">{{ dimensionName }}</h3>
          </div>
          <p class="text-sm text-slate-600">
            Promedio: <strong class="text-slate-900">{{ dimension.average_score }}</strong> / {{ dimension.max_score }} ({{ dimension.percentage }}%)
          </p>
        </div>
        
        <canvas :ref="el => dimensionChartRefs[dimensionName] = el" style="height: 250px"></canvas>
        
        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <div v-for="(count, level) in dimension.distribution" :key="level" v-show="count > 0" class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded" :style="{ backgroundColor: colors[level] }"></div>
              <span class="text-slate-700">{{ labels[level] }}</span>
            </div>
            <span class="font-semibold text-slate-900">{{ count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráfica comparativa de promedios -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
      <h3 class="text-lg font-bold text-slate-900 mb-6">Comparativa de Promedios por Dimensión</h3>
      <canvas ref="comparativeChartRef" style="height: 600px"></canvas>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface DimensionData {
  average_score: number;
  max_score: number;
  percentage: number;
  risk_level: string;
  risk_level_label: string;
  distribution: Record<string, number>;
  total_evaluations: number;
}

interface Props {
  dimensionStatistics?: {
    dimensions: Record<string, DimensionData>;
    total_evaluations: number;
    colors: Record<string, string>;
    labels: Record<string, string>;
  };
}

const props = defineProps<Props>();

const dimensionChartRefs = ref<Record<string, HTMLCanvasElement | null>>({});
const comparativeChartRef = ref<HTMLCanvasElement | null>(null);
const chartInstances = ref<Record<string, Chart>>({});

// Computed properties para acceder fácilmente a las propiedades
const dimensions = computed(() => props.dimensionStatistics?.dimensions || {});
const totalEvaluations = computed(() => props.dimensionStatistics?.total_evaluations || 0);
const colors = computed(() => props.dimensionStatistics?.colors || {
  nulo: '#3B82F6',
  bajo: '#10B981',
  medio: '#F59E0B',
  alto: '#F97316',
  muy_alto: '#EF4444'
});
const labels = computed(() => props.dimensionStatistics?.labels || {
  nulo: 'Nulo',
  bajo: 'Bajo',
  medio: 'Medio',
  alto: 'Alto',
  muy_alto: 'Muy Alto'
});

// Ordenar dimensiones por nivel de riesgo (de mayor a menor)
const sortedDimensionsByRisk = computed(() => {
  const riskOrder = { 'muy_alto': 5, 'alto': 4, 'medio': 3, 'bajo': 2, 'nulo': 1 };
  
  return Object.entries(dimensions.value)
    .sort(([, a], [, b]) => {
      const orderA = riskOrder[a.risk_level as keyof typeof riskOrder] || 0;
      const orderB = riskOrder[b.risk_level as keyof typeof riskOrder] || 0;
      return orderB - orderA;
    })
    .reduce((acc, [name, data]) => {
      acc[name] = data;
      return acc;
    }, {} as Record<string, DimensionData>);
});

// Helper para obtener color con opacidad
const getLevelColor = (level: string, opacity: number): string => {
  const color = colors.value[level];
  if (!color) return `rgba(148, 163, 184, ${opacity})`;
  
  // Convertir hex a rgba
  const hex = color.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);
  
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

const getConsistentStepSize = (maxValue: number): number => {
  if (maxValue <= 10) return 1;
  if (maxValue <= 50) return 5;
  if (maxValue <= 100) return 10;
  if (maxValue <= 500) return 50;
  return Math.ceil(maxValue / 10);
};

const createDimensionChart = (canvasRef: HTMLCanvasElement, dimensionName: string, dimension: DimensionData): void => {
  if (!canvasRef) return;

  const ctx = canvasRef.getContext('2d');
  if (!ctx) return;

  const chartKey = `dimension_${dimensionName}`;
  const existingChart = chartInstances.value[chartKey];
  if (existingChart) {
    existingChart.destroy();
  }

  // Filtrar y ordenar niveles con datos
  const levelsOrder = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  const chartLabels = levelsOrder.filter(level => dimension.distribution[level] > 0);
  const data = chartLabels.map(level => dimension.distribution[level]);
  const backgroundColors = chartLabels.map(level => colors.value[level]);

  const maxValue = Math.max(...data, 0);
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartLabels.map(level => labels.value[level]),
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

const createComparativeChart = (): void => {
  if (!comparativeChartRef.value) return;

  const ctx = comparativeChartRef.value.getContext('2d');
  if (!ctx) return;

  const existingChart = chartInstances.value['comparative'];
  if (existingChart) {
    existingChart.destroy();
  }

  const sortedData = sortedDimensionsByRisk.value;
  const chartLabels = Object.keys(sortedData);
  const data = chartLabels.map(name => sortedData[name].average_score);
  const backgroundColors = chartLabels.map(name => colors.value[sortedData[name].risk_level]);

  const maxValue = Math.max(...data, 0);
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartLabels,
      datasets: [
        {
          label: 'Promedio',
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
      indexAxis: 'y',
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const dimensionName = context.label;
              const dimension = sortedData[dimensionName];
              return `${context.parsed.x} / ${dimension.max_score} (${dimension.percentage}%)`;
            },
          },
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            stepSize,
          },
        },
        y: {
          grid: {
            display: false,
          },
        },
      },
    },
  });

  chartInstances.value['comparative'] = chart;
};

const renderCharts = (): void => {
  if (!props.dimensionStatistics?.dimensions) return;
  
  nextTick(() => {
    // Crear gráficas individuales por dimensión
    Object.entries(dimensions.value).forEach(([dimensionName, dimension]) => {
      const canvasRef = dimensionChartRefs.value[dimensionName];
      if (canvasRef) {
        createDimensionChart(canvasRef, dimensionName, dimension);
      }
    });

    // Crear gráfica comparativa
    createComparativeChart();
  });
};

watch(() => props.dimensionStatistics, () => {
  renderCharts();
}, { deep: true });

onMounted(() => {
  renderCharts();
});
</script>
