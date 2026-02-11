<template>
  <div class="space-y-6">
    <!-- Header con totales -->
    <!-- <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-blue-600">Total Evaluaciones</p>
            <p class="text-2xl font-bold text-blue-900 mt-1">{{ totalEvaluations }}</p>
          </div>
          <DocumentTextIcon class="w-8 h-8 text-blue-400" />
        </div>
      </div>
      
      <div v-for="(level, key) in riskLevelCounts" :key="key" class="rounded-lg p-4 border" :style="{ backgroundColor: getLevelColor(key, 0.1), borderColor: getLevelColor(key, 0.3) }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium" :style="{ color: getLevelColor(key, 0.8) }">{{ labels[key] }}</p>
            <p class="text-2xl font-bold mt-1" :style="{ color: getLevelColor(key, 1) }">{{ level }}</p>
          </div>
          <ChartBarIcon class="w-8 h-8" :style="{ color: getLevelColor(key, 0.5) }" />
        </div>
      </div>
    </div> -->

    <!-- Gráficas por dominio en grid de 2 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div v-for="(domain, domainName) in domains" :key="domainName" class="bg-white rounded-lg p-6 border border-slate-200">
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-lg font-semibold text-slate-900">{{ domainName }}</h3>
            <!-- <span class="px-3 py-1 rounded-full text-xs font-medium" :style="{ backgroundColor: getRiskLevelBgColor(domain.risk_level), color: getRiskLevelTextColor(domain.risk_level) }">
              {{ domain.risk_level_label }}
            </span> -->
          </div>
          <p class="text-xs text-slate-500 mb-1">
            Categoría: {{ domain.category }}
          </p>
          <p class="text-sm text-slate-600">
            Promedio: <strong class="text-slate-900">{{ domain.average_score }}</strong> / {{ domain.max_score }} ({{ domain.percentage }}%)
          </p>
        </div>
        
        <canvas :ref="el => domainChartRefs[domainName] = el" style="height: 250px"></canvas>
        
        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <div v-for="(count, level) in domain.distribution" :key="level" v-show="count > 0" class="flex justify-between items-center">
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
      <h3 class="text-lg font-bold text-slate-900 mb-6">Comparativa de Promedios por Dominio</h3>
      <canvas ref="comparativeChartRef" style="height: 300px"></canvas>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import { DocumentTextIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

Chart.register(...registerables);

interface DomainData {
  average_score: number;
  max_score: number;
  percentage: number;
  risk_level: string;
  risk_level_label: string;
  distribution: Record<string, number>;
  total_evaluations: number;
  category: string; // CORREGIDO: Agregar categoría padre
}

interface Props {
  domains: Record<string, DomainData>;
  totalEvaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

const props = defineProps<Props>();

const domainChartRefs = ref<Record<string, HTMLCanvasElement | null>>({});
const comparativeChartRef = ref<HTMLCanvasElement | null>(null);
const chartInstances = ref<Record<string, Chart>>({});

// Calcular totales por nivel de riesgo
const riskLevelCounts = computed(() => {
  const counts: Record<string, number> = {
    nulo: 0,
    bajo: 0,
    medio: 0,
  };

  Object.values(props.domains).forEach((domain) => {
    Object.entries(domain.distribution).forEach(([level, count]) => {
      if (level !== 'alto' && level !== 'muy_alto') {
        counts[level] = (counts[level] || 0) + count;
      }
    });
  });

  return counts;
});

// Ordenar dominios por nivel de riesgo (de mayor a menor)
const sortedDomainsByRisk = computed(() => {
  const riskOrder = { 'muy_alto': 5, 'alto': 4, 'medio': 3, 'bajo': 2, 'nulo': 1 };
  
  return Object.entries(props.domains)
    .sort(([, a], [, b]) => {
      const orderA = riskOrder[a.risk_level as keyof typeof riskOrder] || 0;
      const orderB = riskOrder[b.risk_level as keyof typeof riskOrder] || 0;
      return orderB - orderA;
    })
    .reduce((acc, [name, data]) => {
      acc[name] = data;
      return acc;
    }, {} as Record<string, DomainData>);
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

const createDomainChart = (canvasRef: HTMLCanvasElement, domainName: string, domain: DomainData): void => {
  if (!canvasRef) return;

  const ctx = canvasRef.getContext('2d');
  if (!ctx) return;

  const chartKey = `domain_${domainName}`;
  const existingChart = chartInstances.value[chartKey];
  if (existingChart) {
    existingChart.destroy();
  }

  // Filtrar y ordenar niveles con datos
  const levelsOrder = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  const labels = levelsOrder.filter(level => domain.distribution[level] > 0);
  const data = labels.map(level => domain.distribution[level]);
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

const createComparativeChart = (): void => {
  if (!comparativeChartRef.value) return;

  const ctx = comparativeChartRef.value.getContext('2d');
  if (!ctx) return;

  const existingChart = chartInstances.value['comparative'];
  if (existingChart) {
    existingChart.destroy();
  }

  const sortedData = sortedDomainsByRisk.value;
  const labels = Object.keys(sortedData);
  const data = labels.map(name => sortedData[name].average_score);
  const backgroundColors = labels.map(name => props.colors[sortedData[name].risk_level]);

  const maxValue = Math.max(...data, 0);
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
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
              const domainName = context.label;
              const domain = sortedData[domainName];
              return `${context.parsed.x} / ${domain.max_score} (${domain.percentage}%)`;
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
  nextTick(() => {
    // Crear gráficas individuales por dominio
    Object.entries(props.domains).forEach(([domainName, domain]) => {
      const canvasRef = domainChartRefs.value[domainName];
      if (canvasRef) {
        createDomainChart(canvasRef, domainName, domain);
      }
    });

    // Crear gráfica comparativa
    createComparativeChart();
  });
};

watch(() => props.domains, () => {
  renderCharts();
}, { deep: true });

onMounted(() => {
  renderCharts();
});
</script>
