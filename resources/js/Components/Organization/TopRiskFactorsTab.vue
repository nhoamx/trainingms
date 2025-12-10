<template>
  <div class="space-y-6">
    <!-- Filtros Demográficos -->
    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Filtros Demográficos</h3>
        <button
          @click="resetFilters"
          class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors"
        >
          ↺ Restablecer Filtros
        </button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Genero -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
          <select
            v-model="filters.gender"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
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
            v-model="filters.contract_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
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
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="position in demographicDetails.positions" :key="position" :value="position">
              {{ position }}
            </option>
          </select>
        </div>

        <!-- Área -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
          <select
            v-model="filters.area"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="area in demographicDetails.areas" :key="area" :value="area">
              {{ area }}
            </option>
          </select>
        </div>

        <!-- Turno -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
          <select
            v-model="filters.shift"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="shift in demographicDetails.shifts" :key="shift" :value="shift">
              {{ shift }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabla de Factores de Riesgo -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Top 3 Factores de Riesgo</h3>
        <p class="text-sm text-gray-600 mt-1">Basado en el total de respuestas de desacuerdo</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Factor de Riesgo</th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full">
                  Totalmente de Acuerdo
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                  De Acuerdo
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                  Desacuerdo
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-red-100 text-red-800 rounded-full">
                  Totalmente Desacuerdo
                </span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(factor, index) in topThreeFactors"
              :key="factor.name"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-semibold text-sm"
                    :class="getSeverityBadgeClass(index)"
                  >
                    {{ index + 1 }}
                  </span>
                  <span class="font-medium text-gray-900">{{ factor.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-green-50 rounded-lg font-semibold text-green-700">
                  {{ factor.counts['Totalmente de Acuerdo'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-50 rounded-lg font-semibold text-blue-700">
                  {{ factor.counts['De Acuerdo'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-yellow-50 rounded-lg font-semibold text-yellow-700">
                  {{ factor.counts['Desacuerdo'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-red-50 rounded-lg font-semibold text-red-700">
                  {{ factor.counts['Totalmente Desacuerdo'] || 0 }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="topThreeFactors.length === 0" class="p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</p>
        <p class="text-gray-600">Intenta cambiar los filtros para ver los factores de riesgo</p>
      </div>
    </div>

    <!-- Gráfica de Comentarios por Factor -->
    <div v-if="commentFactors.length > 0" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">Comentarios por Factor</h3>
      <p class="text-sm text-gray-600 mb-6">Distribución de comentarios según filtros demográficos aplicados</p>
      
      <canvas ref="commentChartCanvas" style="height: 300px"></canvas>
      
      <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
        <div v-for="(count, label) in commentCounts" :key="label" class="flex justify-between">
          <span class="text-gray-700">{{ label }}</span>
          <span class="font-semibold text-gray-900">{{ count }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State para Comentarios -->
    <div v-else class="bg-gray-50 rounded-lg p-12 text-center border border-gray-200">
      <div class="text-6xl mb-4">💬</div>
      <p class="text-lg font-semibold text-gray-900 mb-2">No hay comentarios disponibles</p>
      <p class="text-gray-600">No se encontraron comentarios para los filtros seleccionados</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface DemographicData {
  gender?: string;
  contract_type?: string;
  position?: string;
  department?: string;
  work_schedule?: string;
}

interface DimensionScore {
  name: string;
  score: number;
  interpretation: string;
}

interface EvaluationComment {
  factor: string;
  comment: string;
}

interface Evaluation {
  id: string;
  demographicData?: DemographicData;
  dimensions?: DimensionScore[];
  comments?: EvaluationComment[];
}

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  areas: string[];
  shifts: string[];
  total_evaluations: number;
}

interface RiskFactor {
  name: string;
  disagreementSum: number;
  counts: Record<string, number>;
}

interface Props {
  evaluations: Evaluation[];
  demographicDetails: DemographicDetails;
}

const props = defineProps<Props>();

// Filters
const filters = ref({
  gender: '',
  contract_type: '',
  position: '',
  area: '',
  shift: '',
});

// Chart refs
const commentChartCanvas = ref<HTMLCanvasElement>();
const chartInstances = ref<Record<string, Chart>>({});

// Reset filters function
const resetFilters = (): void => {
  filters.value.gender = '';
  filters.value.contract_type = '';
  filters.value.position = '';
  filters.value.area = '';
  filters.value.shift = '';
};

// Filtered evaluations
const filteredEvaluations = computed(() => {
  return props.evaluations.filter((evaluation: Evaluation) => {
    const demo = evaluation.demographicData || {};

    if (filters.value.gender && demo.gender !== filters.value.gender) {
      return false;
    }
    if (filters.value.contract_type && demo.contract_type !== filters.value.contract_type) {
      return false;
    }
    if (filters.value.position && demo.position !== filters.value.position) {
      return false;
    }
    if (filters.value.area && demo.department !== filters.value.area) {
      return false;
    }
    if (filters.value.shift && demo.work_schedule !== filters.value.shift) {
      return false;
    }

    return true;
  });
});

// Calculate top 3 risk factors
const topThreeFactors = computed(() => {
  const factorMap: Record<string, Record<string, number>> = {};

  // Aggregate dimension scores
  filteredEvaluations.value.forEach((evaluation: Evaluation) => {
    if (!evaluation.dimensions || !Array.isArray(evaluation.dimensions)) {
      return;
    }

    evaluation.dimensions.forEach((dimension: DimensionScore) => {
      if (!factorMap[dimension.name]) {
        factorMap[dimension.name] = {
          'Totalmente de Acuerdo': 0,
          'De Acuerdo': 0,
          'Desacuerdo': 0,
          'Totalmente Desacuerdo': 0,
        };
      }

      // Map interpretation to agreement level
      const interpretation = dimension.interpretation || '';
      if (factorMap[dimension.name][interpretation] !== undefined) {
        factorMap[dimension.name][interpretation]++;
      }
    });
  });

  // Convert to array and calculate disagreement sum
  const factors: RiskFactor[] = Object.entries(factorMap).map(([name, counts]) => ({
    name,
    counts: counts as Record<string, number>,
    disagreementSum: (counts['Desacuerdo'] || 0) + (counts['Totalmente Desacuerdo'] || 0),
  }));

  // Sort by disagreement sum (descending) and return top 3
  return factors
    .sort((a, b) => b.disagreementSum - a.disagreementSum)
    .slice(0, 3)
    .map((factor) => ({
      name: factor.name,
      counts: factor.counts,
      disagreementSum: factor.disagreementSum,
    }));
});

// Get severity badge color based on rank
const getSeverityBadgeClass = (index: number): string => {
  const severities = [
    'bg-red-600',      // 1st place - worst
    'bg-orange-600',   // 2nd place
    'bg-yellow-600',   // 3rd place
  ];
  return severities[index] || 'bg-gray-600';
};

// Extract unique comment factors from filtered evaluations
const commentFactors = computed(() => {
  const factors = new Set<string>();
  
  filteredEvaluations.value.forEach((evaluation: Evaluation) => {
    if (evaluation.comments && Array.isArray(evaluation.comments)) {
      evaluation.comments.forEach((comment: EvaluationComment) => {
        if (comment.factor) {
          factors.add(comment.factor);
        }
      });
    }
  });
  
  return Array.from(factors).sort();
});

// Count comments by factor
const commentCounts = computed(() => {
  const counts: Record<string, number> = {};
  
  filteredEvaluations.value.forEach((evaluation: Evaluation) => {
    if (evaluation.comments && Array.isArray(evaluation.comments)) {
      evaluation.comments.forEach((comment: EvaluationComment) => {
        if (comment.factor) {
          counts[comment.factor] = (counts[comment.factor] || 0) + 1;
        }
      });
    }
  });
  
  return counts;
});

// Chart color helper
const getChartColor = (index: number): string => {
  const colors = [
    'rgba(59, 130, 246, 0.8)',    // Blue
    'rgba(34, 197, 94, 0.8)',     // Green
    'rgba(239, 68, 68, 0.8)',     // Red
    'rgba(251, 146, 60, 0.8)',    // Orange
    'rgba(168, 85, 247, 0.8)',    // Purple
    'rgba(14, 165, 233, 0.8)',    // Cyan
    'rgba(236, 72, 153, 0.8)',    // Pink
    'rgba(100, 116, 139, 0.8)',   // Slate
  ];
  return colors[index % colors.length];
};

const getConsistentStepSize = (maxValue: number): number => {
  if (maxValue <= 10) return 1;
  if (maxValue <= 50) return 5;
  if (maxValue <= 100) return 10;
  if (maxValue <= 500) return 50;
  if (maxValue <= 1000) return 100;
  if (maxValue <= 5000) return 500;
  return Math.ceil(maxValue / 5 / 100) * 100;
};

// Create chart for comments
const createCommentChart = (): void => {
  if (!commentChartCanvas.value) return;

  const ctx = commentChartCanvas.value.getContext('2d');
  if (!ctx) return;

  // Destroy existing chart
  const existingChart = chartInstances.value['comments'];
  if (existingChart) {
    existingChart.destroy();
  }

  const labels = Object.keys(commentCounts.value).filter(label => commentCounts.value[label] > 0);
  const data = labels.map(label => commentCounts.value[label]);
  const backgroundColors = labels.map((_, index) => getChartColor(index));

  const maxValue = data.length > 0 ? Math.max(...data) : 0;
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: backgroundColors,
          borderColor: backgroundColors.map(c => c.replace('0.8', '1')),
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
        y: {
          beginAtZero: true,
          ticks: {
            stepSize,
          },
        },
      },
    },
  });

  chartInstances.value['comments'] = chart;
};

// Render comment chart
const renderCommentChart = (): void => {
  nextTick(() => {
    if (commentFactors.value.length > 0) {
      createCommentChart();
    }
  });
};

// Watch for changes in filtered evaluations and re-render chart
watch([filteredEvaluations, commentFactors], () => {
  renderCommentChart();
}, { deep: true });

// Render chart on mount
onMounted(() => {
  renderCommentChart();
});
</script>
