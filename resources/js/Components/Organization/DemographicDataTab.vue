<template>
  <div>
    <!-- Filtros -->
    <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Filtros Demográficos</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Género -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
          <select
            v-model="filters.gender"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="gender in demographicDetails.genders" :key="gender" :value="gender">
              {{ gender }}
            </option>
          </select>
        </div>

        <!-- Tipo de Contrato -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato</label>
          <select
            v-model="filters.contractType"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="type in demographicDetails.contract_types" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Puesto -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Puesto</label>
          <select
            v-model="filters.position"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="position in demographicDetails.positions" :key="position" :value="position">
              {{ position }}
            </option>
          </select>
        </div>

        <!-- Área -->
        <div>
          <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
            Área
          </label>
          <select
            id="area"
            v-model="filters.area"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todas</option>
            <option v-for="area in demographicDetails.areas" :key="area" :value="area">
              {{ area }}
            </option>
          </select>
        </div>

        <!-- Turno -->
        <div>
          <label for="shift" class="block text-sm font-medium text-gray-700 mb-2">
            Turno
          </label>
          <select
            id="shift"
            v-model="filters.shift"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Todos</option>
            <option v-for="shift in demographicDetails.shifts" :key="shift" :value="shift">
              {{ shift }}
            </option>
          </select>
        </div>

        <!-- Botón Limpiar Filtros -->
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="w-full px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition-colors font-medium"
          >
            Limpiar Filtros
          </button>
        </div>
      </div>

      <!-- Resumen de Filtros -->
      <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-900">
          <strong>Registros mostrados:</strong> {{ filteredEvaluations.length }} de {{ demographicDetails.total_evaluations }}
        </p>
      </div>
    </div>

    <!-- Gráficas Clima Laboral -->
    <div v-if="filteredEvaluations.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Distribución por Nivel (Izquierda) -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Distribución Clima Laboral</h3>
        <canvas ref="climaChartCanvas"></canvas>
        
        <!-- Leyenda con conteos -->
        <div class="mt-6 space-y-2">
          <div v-for="(count, level) in climaDistribution" :key="level" class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div :class="['w-4 h-4 rounded', getLevelColorClass(level)]"></div>
              <span class="text-sm font-medium text-gray-700">{{ level }}</span>
            </div>
            <span class="text-sm font-semibold text-gray-900">{{ count }} ({{ getPercentage(count) }}%)</span>
          </div>
        </div>
      </div>

      <!-- Gráfica de Barras (Derecha) -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Puntaje Promedio por Nivel</h3>
        <canvas ref="barChartCanvas" style="height: 300px"></canvas>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-else class="bg-gray-50 rounded-lg p-12 text-center">
      <div class="text-4xl mb-4">📭</div>
      <p class="text-gray-600 text-lg">No hay datos que coincidan con los filtros seleccionados</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
// import { Chart, registerables } from 'chart.js/auto';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  areas: string[];
  shifts: string[];
  total_evaluations: number;
}

interface DemographicData {
  gender?: string;
  contract_type?: string;
  position?: string;
  department?: string;
  work_schedule?: string;
}

interface Evaluation {
  id: string;
  demographicData?: DemographicData;
  total_score?: number;
  interpretation?: string;
}

interface Props {
  demographicDetails: DemographicDetails;
  evaluations: Evaluation[];
}

const props = withDefaults(defineProps<Props>(), {
  demographicDetails: () => ({
    genders: [],
    contract_types: [],
    positions: [],
    areas: [],
    shifts: [],
    total_evaluations: 0,
  }),
  evaluations: () => [],
});

const filters = ref({
  gender: '',
  contractType: '',
  position: '',
  area: '',
  shift: '',
});

const climaChartCanvas = ref<HTMLCanvasElement>();
const barChartCanvas = ref<HTMLCanvasElement>();
const chartInstances = ref<Record<string, Chart>>({});

// Evaluaciones filtradas
const filteredEvaluations = computed(() => {
  return props.evaluations.filter(evaluation => {
    const demo = evaluation.demographicData;
    if (!demo) return false;

    if (filters.value.gender && demo.gender !== filters.value.gender) return false;
    if (filters.value.contractType && demo.contract_type !== filters.value.contractType) return false;
    if (filters.value.position && demo.position !== filters.value.position) return false;
    if (filters.value.area && demo.department !== filters.value.area) return false;
    if (filters.value.shift && demo.work_schedule !== filters.value.shift) return false;

    return true;
  });
});

// Distribución de Clima Laboral
const climaDistribution = computed(() => {
  const distribution = {
    'Totalmente de Acuerdo': 0,
    'De Acuerdo': 0,
    'Desacuerdo': 0,
    'Totalmente Desacuerdo': 0,
  };

  filteredEvaluations.value.forEach(evaluation => {
    const interpretation = evaluation.interpretation;
    if (interpretation && distribution.hasOwnProperty(interpretation)) {
      distribution[interpretation as keyof typeof distribution]++;
    }
  });

  return distribution;
});

// Promedio de puntaje por nivel
const averageScoresByLevel = computed(() => {
  const scores = {
    'Totalmente de Acuerdo': { total: 0, count: 0 },
    'De Acuerdo': { total: 0, count: 0 },
    'Desacuerdo': { total: 0, count: 0 },
    'Totalmente Desacuerdo': { total: 0, count: 0 },
  };

  filteredEvaluations.value.forEach(evaluation => {
    const interpretation = evaluation.interpretation;
    const score = evaluation.total_score || 0;

    if (interpretation && scores.hasOwnProperty(interpretation)) {
      scores[interpretation as keyof typeof scores].total += score;
      scores[interpretation as keyof typeof scores].count++;
    }
  });

  return Object.entries(scores).map(([level, data]) => ({
    level,
    average: data.count > 0 ? (data.total / data.count).toFixed(2) : 0,
  }));
});

const getLevelColor = (level: string): string => {
  const colors: Record<string, string> = {
    'Totalmente de Acuerdo': 'rgba(96, 165, 250, 0.8)',
    'De Acuerdo': 'rgba(22, 163, 74, 0.8)',
    'Desacuerdo': 'rgba(234, 179, 8, 0.8)',
    'Totalmente Desacuerdo': 'rgba(220, 38, 38, 0.8)',
  };
  return colors[level] || 'rgba(156, 163, 175, 0.8)';
};

const getLevelColorClass = (level: string): string => {
  const classes: Record<string, string> = {
    'Totalmente de Acuerdo': 'bg-blue-400',
    'De Acuerdo': 'bg-green-600',
    'Desacuerdo': 'bg-yellow-500',
    'Totalmente Desacuerdo': 'bg-red-600',
  };
  return classes[level] || 'bg-gray-400';
};

const getPercentage = (count: number): string => {
  if (filteredEvaluations.value.length === 0) return '0';
  const percentage = (count / filteredEvaluations.value.length) * 100;
  return percentage.toFixed(1);
};

const resetFilters = (): void => {
  filters.value = {
    gender: '',
    contractType: '',
    position: '',
    area: '',
    shift: '',
  };
};

const createClimaChart = (): void => {
  if (!climaChartCanvas.value) return;

  const ctx = climaChartCanvas.value.getContext('2d');
  if (!ctx) return;

  const existingChart = chartInstances.value['clima'];
  if (existingChart) {
    existingChart.destroy();
  }

  const labels = Object.keys(climaDistribution.value).filter(
    key => climaDistribution.value[key as keyof typeof climaDistribution.value] > 0
  );
  const data = labels.map(key => climaDistribution.value[key as keyof typeof climaDistribution.value]);
  const colors = labels.map(label => getLevelColor(label));

  const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: colors,
          borderColor: '#fff',
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false,
        },
      },
    },
  });

  chartInstances.value['clima'] = chart;
};

const createBarChart = (): void => {
  if (!barChartCanvas.value) return;

  const ctx = barChartCanvas.value.getContext('2d');
  if (!ctx) return;

  const existingChart = chartInstances.value['bar'];
  if (existingChart) {
    existingChart.destroy();
  }

  const labels = averageScoresByLevel.value.map(item => item.level);
  const data = averageScoresByLevel.value.map(item => parseFloat(item.average as string));
  const colors = labels.map(label => getLevelColor(label));

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Puntaje Promedio',
          data,
          backgroundColor: colors,
          borderColor: colors.map(c => c.replace('0.8', '1')),
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
      },
      scales: {
        x: {
          beginAtZero: true,
          max: 100,
        },
      },
    },
  });

  chartInstances.value['bar'] = chart;
};

const renderCharts = (): void => {
  nextTick(() => {
    if (filteredEvaluations.value.length > 0) {
      createClimaChart();
      createBarChart();
    }
  });
};

watch(() => filteredEvaluations.value.length, () => {
  renderCharts();
});

watch([() => props.evaluations, () => props.demographicDetails], () => {
  renderCharts();
}, { deep: true });

// Renderizar gráficas al montar
import { onMounted } from 'vue';
onMounted(() => {
  renderCharts();
});
</script>
