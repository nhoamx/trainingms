<template>
  <div class="space-y-6">
    <div v-if="dimensions && Object.keys(dimensions).length > 0" class="space-y-6">
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

    <div v-else class="bg-red-50 border border-red-200 rounded p-4 text-sm text-red-800">
      <p><strong>No se encontraron dimensiones para mostrar.</strong></p>
      <p>Verifica que existan evaluaciones procesadas.</p>
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
  dimensions: Record<string, DimensionData>;
  totalEvaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

const props = defineProps<Props>();

const dimensionChartRefs = ref<Record<string, HTMLCanvasElement | null>>({});
const comparativeChartRef = ref<HTMLCanvasElement | null>(null);
const chartInstances = ref<Record<string, Chart>>({})

// Ordenar dimensiones por nivel de riesgo (de mayor a menor)
const sortedDimensionsByRisk = computed(() => {
  const riskOrder = { 'muy_alto': 5, 'alto': 4, 'medio': 3, 'bajo': 2, 'nulo': 1 };
  
  return Object.entries(props.dimensions)
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
  const color = props.colors[level];
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
  console.log(`🎨 createDimensionChart called for: ${dimensionName}`);
  if (!canvasRef) {
    console.error(`❌ No canvas ref for dimension: ${dimensionName}`);
    return;
  }

  const ctx = canvasRef.getContext('2d');
  if (!ctx) {
    console.error(`❌ No 2d context for dimension: ${dimensionName}`);
    return;
  }

  console.log(`✅ Canvas and context ready for: ${dimensionName}`);

  const chartKey = `dimension_${dimensionName}`;
  const existingChart = chartInstances.value[chartKey];
  if (existingChart) {
    console.log(`♻️ Destroying existing chart for: ${dimensionName}`);
    existingChart.destroy();
  }

  // Filtrar y ordenar niveles con datos
  const levelsOrder = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  const chartLabels = levelsOrder.filter(level => dimension.distribution[level] > 0);
  const data = chartLabels.map(level => dimension.distribution[level]);
  const backgroundColors = chartLabels.map(level => props.colors[level]);

  console.log(`📊 Chart data for ${dimensionName}:`, {
    labels: chartLabels,
    data,
    colors: backgroundColors,
  });

  const maxValue = Math.max(...data, 0);
  const stepSize = getConsistentStepSize(maxValue);

  console.log(`📈 Creating Chart.js instance for: ${dimensionName}`);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartLabels.map(level => props.labels[level]),
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
  console.log(`✅ Chart created and stored for: ${dimensionName}`);
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
  const backgroundColors = chartLabels.map(name => props.colors[sortedData[name].risk_level]);

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
  console.log('🔍 DimensionCharts - renderCharts called');
  console.log('📊 Props dimensions:', props.dimensions);
  console.log('📊 Props totalEvaluations:', props.totalEvaluations);
  console.log('📊 Props colors:', props.colors);
  console.log('📊 Props labels:', props.labels);
  console.log('📊 Dimensions keys:', Object.keys(props.dimensions || {}));
  console.log('📊 Dimensions count:', Object.keys(props.dimensions || {}).length);
  
  if (!props.dimensions || Object.keys(props.dimensions).length === 0) {
    console.warn('⚠️ DimensionCharts - No dimensions data available');
    return;
  }
  
  console.log('✅ DimensionCharts - Has dimensions, proceeding to render');
  
  nextTick(() => {
    console.log('🎨 DimensionCharts - nextTick executing');
    // Crear gráficas individuales por dimensión
    Object.entries(props.dimensions).forEach(([dimensionName, dimension]) => {
      console.log(`📈 Creating chart for dimension: ${dimensionName}`, dimension);
      const canvasRef = dimensionChartRefs.value[dimensionName];
      console.log(`🖼️ Canvas ref for ${dimensionName}:`, canvasRef);
      if (canvasRef) {
        createDimensionChart(canvasRef, dimensionName, dimension);
      } else {
        console.warn(`⚠️ No canvas ref found for dimension: ${dimensionName}`);
      }
    });

    // Crear gráfica comparativa
    console.log('📊 Creating comparative chart');
    createComparativeChart();
  });
};

watch(() => props.dimensions, () => {
  console.log('👀 DimensionCharts - Watch triggered, dimensions changed');
  renderCharts();
}, { deep: true });

onMounted(() => {
  console.log('🚀 DimensionCharts - Component mounted');
  console.log('📦 Initial props:', {
    dimensions: props.dimensions,
    totalEvaluations: props.totalEvaluations,
    colors: props.colors,
    labels: props.labels,
  });
  renderCharts();
});
</script>
