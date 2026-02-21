<template>
  <div v-if="blocksData && Object.keys(blocksData).length > 0" class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
      <h3 class="text-lg font-semibold text-slate-900 mb-2">
        Análisis por Bloques Temáticos
      </h3>
      <p class="text-sm text-slate-600 mb-4">
        Distribución de respuestas agrupadas por los {{ Object.keys(blocksData).length }} bloques temáticos del cuestionario
      </p>

      <!-- Summary -->
      <div class="flex items-center gap-6 text-sm">
        <div class="flex items-center gap-2">
          <span class="font-medium text-slate-700">Total bloques:</span>
          <span class="text-slate-900 font-semibold">{{ Object.keys(blocksData).length }}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="font-medium text-slate-700">Total evaluaciones:</span>
          <span class="text-slate-900 font-semibold">{{ totalEvaluations }}</span>
        </div>
      </div>

      <!-- Response Legend -->
      <div class="mt-6 flex flex-wrap gap-4 text-xs">
        <div v-for="legendItem in responseLegendItems" :key="legendItem.key" class="flex items-center gap-2">
          <div class="w-4 h-4 rounded" :style="{ backgroundColor: legendItem.color }"></div>
          <span class="text-slate-600"><strong>{{ legendItem.label }}</strong> - {{ legendItem.description }}</span>
        </div>
      </div>
    </div>

    <!-- Blocks Grid -->
    <div class="grid grid-cols-1 gap-6">
      <div
        v-for="block in sortedBlocks"
        :key="block.block_number"
        class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden"
      >
        <!-- Block Header -->
        <div class="bg-gradient-to-r from-blue-50 to-slate-50 px-6 py-4 border-b border-slate-200">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl font-bold text-blue-600">
                  {{ block.block_number }}
                </span>
                <div>
                  <h4 class="text-base font-semibold text-slate-900">
                    Bloque {{ block.block_number }}
                  </h4>
                  <p class="text-sm text-slate-600">
                    {{ block.question_count }} pregunta{{ block.question_count !== 1 ? 's' : '' }}
                    ({{ formatQuestionRange(block.questions) }})
                  </p>
                </div>
              </div>
              <p class="text-sm text-slate-700 italic">
                {{ block.instructions }}
              </p>
            </div>
            <div class="ml-4">
              <span
                :class="getCriticalityClass(block.criticality)"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
              >
                {{ getCriticalityLabel(block.criticality) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Block Content -->
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Statistics -->
            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">{{ isBinaryMode ? 'Sí (%)' : 'Promedio:' }}</span>
                <span class="font-semibold text-slate-900">{{ isBinaryMode ? getYesPercentage(block) + '%' : block.average_score }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Total respuestas:</span>
                <span class="font-semibold text-slate-900">{{ block.total_responses }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Respuestas negativas:</span>
                <span class="font-semibold" :class="getNegativePercentageClass(block.negative_percentage)">
                  {{ block.negative_percentage }}%
                </span>
              </div>

              <!-- Response Breakdown -->
              <div class="mt-4 space-y-2">
                <div
                  v-for="item in getResponseItems(block.responses)"
                  :key="item.key"
                  class="flex items-center gap-2 text-xs"
                >
                  <div class="w-3 h-3 rounded" :style="{ backgroundColor: responseColors[item.key] }"></div>
                  <span class="text-slate-600 capitalize w-24">{{ formatResponseLabel(item.key) }}</span>
                  <span class="font-semibold text-slate-900">{{ item.count }}</span>
                  <span class="text-slate-500">
                    ({{ block.total_responses > 0 ? ((item.count / block.total_responses) * 100).toFixed(1) : 0 }}%)
                  </span>
                </div>
              </div>
            </div>

            <!-- Chart -->
            <div>
              <canvas :ref="el => chartRefs[block.block_number] = el as HTMLCanvasElement"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Estado vacío -->
  <div v-else class="bg-slate-50 rounded-lg p-12 text-center border border-slate-200">
    <DocumentTextIcon class="w-16 h-16 text-slate-400 mx-auto mb-4" />
    <h3 class="text-lg font-semibold text-slate-900 mb-2">
      No hay datos de bloques disponibles
    </h3>
    <p class="text-sm text-slate-600">
      Las estadísticas por bloque se generarán una vez que haya evaluaciones completadas.
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';

Chart.register(...registerables);

interface BlockData {
  block_number: number;
  instructions: string;
  question_count: number;
  questions: number[];
  responses: {
    siempre: number;
    casi_siempre: number;
    algunas_veces: number;
    casi_nunca: number;
    nunca: number;
  };
  total_responses: number;
  average_score: number;
  negative_percentage: number;
  criticality: 'low' | 'medium' | 'high' | 'critical';
}

interface Props {
  blocksData: Record<string, BlockData>;
  totalEvaluations: number;
  binaryMode?: boolean;
}

const props = defineProps<Props>();

type ResponseKey = keyof BlockData['responses'];

const isBinaryMode = computed(() => props.binaryMode === true);

// Estados
const chartRefs = ref<Record<number, HTMLCanvasElement | null>>({});
const chartInstances = ref<Record<number, Chart>>({});

// Response colors matching other charts
const responseColors = {
  siempre: '#10B981',
  casi_siempre: '#3B82F6',
  algunas_veces: '#F59E0B',
  casi_nunca: '#F97316',
  nunca: '#EF4444',
};

const responseLegendItems = computed(() => {
  if (isBinaryMode.value) {
    return [
      { key: 'siempre', label: 'Sí', description: 'Respuesta afirmativa', color: responseColors.siempre },
      { key: 'nunca', label: 'No', description: 'Respuesta negativa', color: responseColors.nunca },
    ];
  }

  return [
    { key: 'siempre', label: 'Siempre', description: 'Respuesta favorable', color: responseColors.siempre },
    { key: 'casi_siempre', label: 'Casi siempre', description: 'Favorable', color: responseColors.casi_siempre },
    { key: 'algunas_veces', label: 'Algunas veces', description: 'Neutro', color: responseColors.algunas_veces },
    { key: 'casi_nunca', label: 'Casi nunca', description: 'Desfavorable', color: responseColors.casi_nunca },
    { key: 'nunca', label: 'Nunca', description: 'Muy desfavorable', color: responseColors.nunca },
  ];
});

// Computed: Sorted blocks
const sortedBlocks = computed(() => {
  if (!props.blocksData) return [];
  
  return Object.values(props.blocksData).sort((a, b) => a.block_number - b.block_number);
});

// Format question range
const formatQuestionRange = (questions: number[]): string => {
  if (!questions || questions.length === 0) return '';
  const sorted = [...questions].sort((a, b) => a - b);
  const first = sorted[0];
  const last = sorted[sorted.length - 1];
  return first === last ? `Pregunta ${first}` : `Preguntas ${first}-${last}`;
};

// Format response label
const formatResponseLabel = (response: ResponseKey): string => {
  if (isBinaryMode.value) {
    const binaryLabels: Record<ResponseKey, string> = {
      siempre: 'sí',
      casi_siempre: 'casi siempre',
      algunas_veces: 'algunas veces',
      casi_nunca: 'casi nunca',
      nunca: 'no',
    };

    return binaryLabels[response] ?? response;
  }

  return response.replace('_', ' ');
};

const getResponseItems = (responses: BlockData['responses']): Array<{ key: ResponseKey; count: number }> => {
  if (isBinaryMode.value) {
    return [
      { key: 'siempre', count: responses.siempre || 0 },
      { key: 'nunca', count: responses.nunca || 0 },
    ];
  }

  return [
    { key: 'siempre', count: responses.siempre || 0 },
    { key: 'casi_siempre', count: responses.casi_siempre || 0 },
    { key: 'algunas_veces', count: responses.algunas_veces || 0 },
    { key: 'casi_nunca', count: responses.casi_nunca || 0 },
    { key: 'nunca', count: responses.nunca || 0 },
  ];
};

const getYesPercentage = (block: BlockData): string => {
  const yesResponses = block.responses.siempre || 0;
  const noResponses = block.responses.nunca || 0;
  const totalResponses = yesResponses + noResponses;

  if (totalResponses === 0) {
    return '0.0';
  }

  return ((yesResponses / totalResponses) * 100).toFixed(1);
};

// Get criticality class
const getCriticalityClass = (criticality: string): string => {
  const classes = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    critical: 'bg-red-100 text-red-800',
  };
  return classes[criticality] || classes.low;
};

// Get criticality label
const getCriticalityLabel = (criticality: string): string => {
  const labels = {
    low: 'Bajo riesgo',
    medium: 'Riesgo medio',
    high: 'Riesgo alto',
    critical: 'Crítico',
  };
  return labels[criticality] || 'Bajo riesgo';
};

// Get negative percentage class
const getNegativePercentageClass = (percentage: number): string => {
  if (percentage >= 50) return 'text-red-600';
  if (percentage >= 30) return 'text-orange-600';
  if (percentage >= 15) return 'text-yellow-600';
  return 'text-green-600';
};

// Create chart for a block
const createBlockChart = (block: BlockData) => {
  nextTick(() => {
    const canvas = chartRefs.value[block.block_number];
    if (!canvas) return;

    // Destroy existing chart if any
    if (chartInstances.value[block.block_number]) {
      chartInstances.value[block.block_number].destroy();
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const responses = block.responses;
    const total = block.total_responses;

    const labels = isBinaryMode.value ? ['Sí', 'No'] : ['Siempre', 'Casi siempre', 'Algunas veces', 'Casi nunca', 'Nunca'];
    const data = isBinaryMode.value
      ? [responses.siempre, responses.nunca]
      : [
          responses.siempre,
          responses.casi_siempre,
          responses.algunas_veces,
          responses.casi_nunca,
          responses.nunca,
        ];
    const colors = isBinaryMode.value
      ? [responseColors.siempre, responseColors.nunca]
      : [
          responseColors.siempre,
          responseColors.casi_siempre,
          responseColors.algunas_veces,
          responseColors.casi_nunca,
          responseColors.nunca,
        ];

    chartInstances.value[block.block_number] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Respuestas',
          data,
          backgroundColor: colors,
          borderWidth: 0,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                const value = context.parsed.x;
                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                return `${value} respuestas (${percentage}%)`;
              },
            },
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              precision: 0,
            },
          },
          y: {
            ticks: {
              font: {
                size: 11,
              },
            },
          },
        },
      },
    });
  });
};

// Create all charts
const createAllCharts = () => {
  sortedBlocks.value.forEach(block => {
    createBlockChart(block);
  });
};

// Lifecycle
onMounted(() => {
  createAllCharts();
});

// Watch for data changes
watch(() => props.blocksData, () => {
  createAllCharts();
}, { deep: true });
</script>
