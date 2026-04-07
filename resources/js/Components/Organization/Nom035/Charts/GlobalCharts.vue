<template>
  <div v-if="globalStatistics && globalStatistics.global" class="space-y-6">
    <!-- Global Summary Section -->
    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-6 border border-slate-200">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-xl font-bold text-slate-900">Estadística Global de la Organización</h3>
          <p class="text-sm text-slate-600 mt-1">Resumen agregado de todas las evaluaciones (72 preguntas)</p>
        </div>
        <div class="px-4 py-2 rounded-full text-sm font-semibold" :style="{ backgroundColor: getRiskLevelBgColor(summaryRiskLevel), color: getRiskLevelTextColor(summaryRiskLevel) }">
          {{ summaryRiskLabel }}
        </div>
      </div>

      <!-- Key Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg p-4 border border-slate-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Total de Evaluaciones</p>
              <p class="text-3xl font-bold text-slate-900 mt-1">{{ summaryTotalEvaluations }}</p>
            </div>
            <DocumentTextIcon class="w-10 h-10 text-slate-400" />
          </div>
        </div>

        <div class="bg-white rounded-lg p-4 border border-slate-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Puntuación Promedio</p>
              <p class="text-3xl font-bold text-slate-900 mt-1">{{ summaryAverageScore }} / {{ summaryMaxScore }}</p>
              <p class="text-xs text-slate-500 mt-1">Resultado: {{ summaryPercentage }}%</p>
            </div>
            <ChartBarIcon class="w-10 h-10 text-slate-400" />
          </div>
        </div>

        <div class="bg-white rounded-lg p-4 border border-slate-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Porcentaje</p>
              <p class="text-3xl font-bold text-slate-900 mt-1">{{ summaryPercentage }}%</p>
              <p class="text-xs text-slate-500 mt-1">respecto al máximo</p>
            </div>
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl font-bold" :style="{ backgroundColor: getLevelColor(summaryRiskLevel, 0.2), color: getLevelColor(summaryRiskLevel, 1) }">
              %
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Risk Level Distribution Chart -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
      <div class="mb-6">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Distribución Global de Niveles de Riesgo</h3>
        <p class="text-sm text-slate-600">Clasificación de todas las evaluaciones según su nivel de riesgo psicosocial</p>
      </div>

      <canvas ref="riskDistributionChartRef" style="height: 300px"></canvas>

      <!-- Risk Level Legend -->
      <div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
        <div v-for="(count, level) in globalStatistics.global.distribution" :key="level" v-show="count > 0" class="bg-slate-50 rounded-lg p-3">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-4 h-4 rounded" :style="{ backgroundColor: colors[level] }"></div>
            <span class="text-sm font-medium text-slate-700">{{ labels[level] }}</span>
          </div>
          <div class="flex items-baseline gap-1">
            <span class="text-2xl font-bold text-slate-900">{{ count }}</span>
            <span class="text-xs text-slate-500">evaluaciones</span>
          </div>
          <div class="text-xs text-slate-500 mt-1">
            {{ ((count / globalStatistics.total_evaluations) * 100).toFixed(1) }}%
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import { DocumentTextIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

Chart.register(...registerables);

interface GlobalData {
  average_score?: number;
  max_score?: number;
  percentage?: number;
  risk_level?: string;
  risk_level_label?: string;
  distribution?: Record<string, number>;
  [key: string]: unknown;
}

interface Props {
  globalStatistics?: {
    global: GlobalData;
    total_evaluations: number;
    colors: Record<string, string>;
    labels: Record<string, string>;
  };
  generalReport?: {
    total_evaluations: number;
    final_average_score?: number;
    final_max_score?: number;
    final_percentage?: number;
    final_risk_level?: string;
    final_risk_label?: string;
  };
}

const props = defineProps<Props>();

const riskDistributionChartRef = ref<HTMLCanvasElement | null>(null);
const chartInstance = ref<Chart | null>(null);

// Extraer colors y labels directamente para uso en template con valores por defecto
const colors = props.globalStatistics?.colors || {
  nulo: '#3B82F6',
  bajo: '#10B981',
  medio: '#F59E0B',
  alto: '#F97316',
  muy_alto: '#EF4444'
};
const labels = props.globalStatistics?.labels || {
  nulo: 'Nulo',
  bajo: 'Bajo',
  medio: 'Medio',
  alto: 'Alto',
  muy_alto: 'Muy Alto'
};

const normalizeRiskLevel = (riskLevel?: string | null): string => {
  if (!riskLevel || typeof riskLevel !== 'string') {
    return 'nulo';
  }

  return riskLevel.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').replace(/\s+/g, '_');
};

const getRiskLabel = (level: string): string => {
  return labels[level] ?? level;
};

const summaryTotalEvaluations = computed(() => {
  const total = Number(props.generalReport?.total_evaluations ?? props.globalStatistics?.total_evaluations ?? 0);
  return Number.isFinite(total) ? Math.max(0, Math.round(total)) : 0;
});

const summaryAverageScore = computed(() => {
  const average = Number(props.generalReport?.final_average_score ?? props.globalStatistics?.global?.average_score ?? 0);
  return Number.isFinite(average) ? Math.max(0, Math.round(average)) : 0;
});

const summaryMaxScore = computed(() => {
  const max = Number(props.generalReport?.final_max_score ?? props.globalStatistics?.global?.max_score ?? 288);
  return Number.isFinite(max) ? Math.max(0, Math.round(max)) : 288;
});

const summaryPercentage = computed(() => {
  const fallback = summaryMaxScore.value > 0 ? (summaryAverageScore.value / summaryMaxScore.value) * 100 : 0;
  const percentage = Number(props.generalReport?.final_percentage ?? props.globalStatistics?.global?.percentage ?? fallback);
  return Number.isFinite(percentage) ? percentage.toFixed(2) : '0.00';
});

const summaryRiskLevel = computed(() => normalizeRiskLevel(props.generalReport?.final_risk_level ?? props.globalStatistics?.global?.risk_level ?? 'nulo'));
const summaryRiskLabel = computed(() => props.generalReport?.final_risk_label ?? props.globalStatistics?.global?.risk_level_label ?? getRiskLabel(summaryRiskLevel.value));

// Helper para obtener color con opacidad
const getLevelColor = (level: string, opacity: number): string => {
  const color = colors[level];
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
  return colors[level] || '#64748b';
};

const getConsistentStepSize = (maxValue: number): number => {
  if (maxValue <= 10) return 1;
  if (maxValue <= 50) return 5;
  if (maxValue <= 100) return 10;
  if (maxValue <= 500) return 50;
  return Math.ceil(maxValue / 10);
};

const createRiskDistributionChart = (): void => {
  if (!riskDistributionChartRef.value) return;

  const ctx = riskDistributionChartRef.value.getContext('2d');
  if (!ctx) return;

  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  // Ordenar niveles en orden de severidad
  const levelsOrder = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  const distribution = props.globalStatistics?.global?.distribution || {};
  
  // Filtrar solo niveles con datos
  const levelsWithData = levelsOrder.filter(level => (distribution[level] || 0) > 0);
  const data = levelsWithData.map(level => distribution[level] || 0);
  const backgroundColors = levelsWithData.map(level => colors[level]);
  const levelLabels = levelsWithData.map(level => labels[level]);

  const maxValue = Math.max(...data, 0);
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: levelLabels,
      datasets: [
        {
          label: 'Número de evaluaciones',
          data,
          backgroundColor: backgroundColors,
          borderColor: backgroundColors,
          borderWidth: 2,
          borderRadius: 6,
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
              const count = context.parsed.x;
              const total = props.globalStatistics.total_evaluations;
              const percentage = ((count / total) * 100).toFixed(1);
              return `${count} evaluaciones (${percentage}%)`;
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
          grid: {
            color: 'rgba(203, 213, 225, 0.3)',
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

  chartInstance.value = chart;
};

const renderChart = (): void => {
  nextTick(() => {
    createRiskDistributionChart();
  });
};

watch(() => props.globalStatistics, () => {
  renderChart();
}, { deep: true });

onMounted(() => {
  renderChart();
});
</script>
