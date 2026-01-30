<template>
  <div v-if="questionsData && Object.keys(questionsData).length > 0" class="space-y-6">
    <!-- Resumen global -->
    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-200">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-xl font-bold text-slate-900">Análisis por Pregunta Individual</h3>
          <p class="text-sm text-slate-600 mt-1">Distribución de respuestas para cada una de las 72 preguntas del cuestionario</p>
        </div>
        <div class="flex items-center gap-2">
          <DocumentTextIcon class="w-8 h-8 text-purple-600" />
          <div class="text-right">
            <p class="text-sm font-medium text-slate-600">Total preguntas</p>
            <p class="text-2xl font-bold text-slate-900">{{ Object.keys(questionsData).length }}</p>
          </div>
        </div>
      </div>

      <!-- Leyenda de respuestas -->
      <div class="grid grid-cols-5 gap-3 mt-4">
        <div class="bg-white rounded-lg p-3 border border-slate-200 text-center">
          <div class="flex items-center justify-center gap-2 mb-1">
            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
            <span class="text-xs font-medium text-slate-700">Siempre</span>
          </div>
          <p class="text-xs text-slate-500">Respuesta favorable</p>
        </div>
        <div class="bg-white rounded-lg p-3 border border-slate-200 text-center">
          <div class="flex items-center justify-center gap-2 mb-1">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <span class="text-xs font-medium text-slate-700">Casi siempre</span>
          </div>
          <p class="text-xs text-slate-500">Favorable</p>
        </div>
        <div class="bg-white rounded-lg p-3 border border-slate-200 text-center">
          <div class="flex items-center justify-center gap-2 mb-1">
            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
            <span class="text-xs font-medium text-slate-700">Algunas veces</span>
          </div>
          <p class="text-xs text-slate-500">Neutro</p>
        </div>
        <div class="bg-white rounded-lg p-3 border border-slate-200 text-center">
          <div class="flex items-center justify-center gap-2 mb-1">
            <div class="w-3 h-3 rounded-full bg-orange-500"></div>
            <span class="text-xs font-medium text-slate-700">Casi nunca</span>
          </div>
          <p class="text-xs text-slate-500">Desfavorable</p>
        </div>
        <div class="bg-white rounded-lg p-3 border border-slate-200 text-center">
          <div class="flex items-center justify-center gap-2 mb-1">
            <div class="w-3 h-3 rounded-full bg-red-500"></div>
            <span class="text-xs font-medium text-slate-700">Nunca</span>
          </div>
          <p class="text-xs text-slate-500">Muy desfavorable</p>
        </div>
      </div>
    </div>

    <!-- Controles de visualización -->
    <div class="bg-white rounded-lg p-4 border border-slate-200">
      <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-slate-700">Mostrar:</label>
          <select v-model="itemsPerPage" class="rounded-lg border-slate-300 text-sm">
            <option :value="10">10 preguntas</option>
            <option :value="20">20 preguntas</option>
            <option :value="36">36 preguntas</option>
            <option :value="72">Todas (72)</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-slate-700">Ordenar por:</label>
          <select v-model="sortBy" class="rounded-lg border-slate-300 text-sm">
            <option value="number">Número de pregunta</option>
            <option value="criticality">Criticidad (mayor a menor)</option>
            <option value="responses">Más respuestas negativas</option>
          </select>
        </div>
        <button 
          @click="currentPage = 1" 
          v-if="currentPage > 1"
          class="ml-auto px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors"
        >
          Volver al inicio
        </button>
      </div>
    </div>

    <!-- Lista de preguntas -->
    <div class="space-y-4">
      <div 
        v-for="question in paginatedQuestions" 
        :key="question.number"
        class="bg-white rounded-lg border border-slate-200 hover:shadow-md transition-shadow overflow-hidden"
      >
        <!-- Encabezado de la pregunta -->
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white text-sm font-bold">
                  {{ question.number }}
                </span>
                <div>
                  <h4 class="font-semibold text-slate-900 leading-tight">{{ question.text }}</h4>
                  <p class="text-xs text-slate-500 mt-1">
                    <span class="font-medium">{{ question.dimension }}</span> · 
                    <span>{{ question.domain }}</span> · 
                    <span>{{ question.category }}</span>
                  </p>
                </div>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <div 
                class="px-3 py-1 rounded-full text-xs font-semibold"
                :style="{ 
                  backgroundColor: getCriticalityColor(question.criticality).bg, 
                  color: getCriticalityColor(question.criticality).text 
                }"
              >
                {{ getCriticalityLabel(question.criticality) }}
              </div>
              <div class="text-right">
                <p class="text-xs text-slate-500">Promedio</p>
                <p class="text-lg font-bold text-slate-900">{{ question.averageScore }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contenido: gráfico y estadísticas -->
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Gráfico de barras -->
            <div class="lg:col-span-2">
              <canvas :ref="el => chartRefs[question.number] = el" style="height: 200px"></canvas>
            </div>

            <!-- Estadísticas detalladas -->
            <div class="space-y-2">
              <div 
                v-for="(count, response) in question.responses" 
                :key="response"
                class="flex items-center justify-between p-3 rounded-lg border"
                :style="{ 
                  borderColor: getResponseColor(response), 
                  backgroundColor: `${getResponseColor(response)}10` 
                }"
              >
                <div class="flex items-center gap-2">
                  <div 
                    class="w-3 h-3 rounded-full" 
                    :style="{ backgroundColor: getResponseColor(response) }"
                  ></div>
                  <span class="text-sm font-medium text-slate-700">{{ getResponseLabel(response) }}</span>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-slate-900">{{ count }}</p>
                  <p class="text-xs text-slate-500">
                    {{ ((count / totalEvaluations) * 100).toFixed(1) }}%
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Paginación -->
    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-6">
      <button
        @click="currentPage--"
        :disabled="currentPage === 1"
        class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
      >
        Anterior
      </button>
      <div class="flex items-center gap-1">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="currentPage = page"
          :class="[
            'w-10 h-10 rounded-lg text-sm font-medium transition-colors',
            currentPage === page
              ? 'bg-indigo-600 text-white'
              : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
          ]"
        >
          {{ page }}
        </button>
      </div>
      <button
        @click="currentPage++"
        :disabled="currentPage === totalPages"
        class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
      >
        Siguiente
      </button>
    </div>
  </div>

  <!-- Estado vacío -->
  <div v-else class="bg-slate-50 rounded-lg p-12 text-center border border-slate-200">
    <DocumentTextIcon class="w-16 h-16 text-slate-400 mx-auto mb-4" />
    <h3 class="text-lg font-semibold text-slate-900 mb-2">
      No hay datos de preguntas disponibles
    </h3>
    <p class="text-sm text-slate-600">
      Las estadísticas por pregunta se generarán una vez que haya evaluaciones completadas.
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';

Chart.register(...registerables);

interface QuestionData {
  number: number;
  text: string;
  category: string;
  domain: string;
  dimension: string;
  responses: {
    siempre: number;
    casi_siempre: number;
    algunas_veces: number;
    casi_nunca: number;
    nunca: number;
  };
  averageScore: number;
  criticality: 'low' | 'medium' | 'high' | 'critical';
}

interface Props {
  questionsData: Record<string, QuestionData>;
  totalEvaluations: number;
}

const props = defineProps<Props>();

// Estados
const chartRefs = ref<Record<number, HTMLCanvasElement | null>>({});
const chartInstances = ref<Record<number, Chart>>({});
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref('number');

// Colores por tipo de respuesta
const responseColors = {
  siempre: '#10B981',      // Verde
  casi_siempre: '#3B82F6', // Azul
  algunas_veces: '#F59E0B', // Amarillo
  casi_nunca: '#F97316',   // Naranja
  nunca: '#EF4444'         // Rojo
};

const getResponseColor = (response: string): string => {
  return responseColors[response as keyof typeof responseColors] || '#94A3B8';
};

const getResponseLabel = (response: string): string => {
  const labels: Record<string, string> = {
    siempre: 'Siempre',
    casi_siempre: 'Casi siempre',
    algunas_veces: 'Algunas veces',
    casi_nunca: 'Casi nunca',
    nunca: 'Nunca'
  };
  return labels[response] || response;
};

const getCriticalityColor = (criticality: string) => {
  const colors: Record<string, { bg: string; text: string }> = {
    low: { bg: '#D1FAE5', text: '#065F46' },      // Verde
    medium: { bg: '#FEF3C7', text: '#92400E' },   // Amarillo
    high: { bg: '#FED7AA', text: '#9A3412' },     // Naranja
    critical: { bg: '#FEE2E2', text: '#991B1B' }  // Rojo
  };
  return colors[criticality] || { bg: '#F1F5F9', text: '#475569' };
};

const getCriticalityLabel = (criticality: string): string => {
  const labels: Record<string, string> = {
    low: 'Bajo riesgo',
    medium: 'Riesgo medio',
    high: 'Riesgo alto',
    critical: 'Crítico'
  };
  return labels[criticality] || 'Sin clasificar';
};

// Preguntas ordenadas y filtradas
const sortedQuestions = computed(() => {
  const questions = Object.values(props.questionsData);
  
  switch (sortBy.value) {
    case 'criticality':
      const criticalityOrder = { critical: 4, high: 3, medium: 2, low: 1 };
      return questions.sort((a, b) => {
        const orderA = criticalityOrder[a.criticality] || 0;
        const orderB = criticalityOrder[b.criticality] || 0;
        return orderB - orderA;
      });
    
    case 'responses':
      return questions.sort((a, b) => {
        const negativeA = (a.responses.casi_nunca || 0) + (a.responses.nunca || 0);
        const negativeB = (b.responses.casi_nunca || 0) + (b.responses.nunca || 0);
        return negativeB - negativeA;
      });
    
    default: // 'number'
      return questions.sort((a, b) => a.number - b.number);
  }
});

const totalPages = computed(() => {
  return Math.ceil(sortedQuestions.value.length / itemsPerPage.value);
});

const paginatedQuestions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return sortedQuestions.value.slice(start, end);
});

const visiblePages = computed(() => {
  const pages: number[] = [];
  const total = totalPages.value;
  const current = currentPage.value;
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i);
      pages.push(-1); // Ellipsis
      pages.push(total);
    } else if (current >= total - 3) {
      pages.push(1);
      pages.push(-1); // Ellipsis
      for (let i = total - 4; i <= total; i++) pages.push(i);
    } else {
      pages.push(1);
      pages.push(-1); // Ellipsis
      for (let i = current - 1; i <= current + 1; i++) pages.push(i);
      pages.push(-1); // Ellipsis
      pages.push(total);
    }
  }
  
  return pages.filter(p => p !== -1);
});

const createQuestionChart = (question: QuestionData) => {
  const canvasRef = chartRefs.value[question.number];
  if (!canvasRef) return;

  const ctx = canvasRef.getContext('2d');
  if (!ctx) return;

  // Destroy existing chart if it exists
  if (chartInstances.value[question.number]) {
    chartInstances.value[question.number].destroy();
  }

  const responses = question.responses;
  const labels = ['Siempre', 'Casi siempre', 'Algunas veces', 'Casi nunca', 'Nunca'];
  const data = [
    responses.siempre || 0,
    responses.casi_siempre || 0,
    responses.algunas_veces || 0,
    responses.casi_nunca || 0,
    responses.nunca || 0
  ];
  const colors = [
    responseColors.siempre,
    responseColors.casi_siempre,
    responseColors.algunas_veces,
    responseColors.casi_nunca,
    responseColors.nunca
  ];

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Número de respuestas',
        data,
        backgroundColor: colors,
        borderColor: colors,
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const count = context.parsed.y;
              const percentage = ((count / props.totalEvaluations) * 100).toFixed(1);
              return `${count} respuestas (${percentage}%)`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          },
          grid: {
            color: 'rgba(203, 213, 225, 0.3)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

  chartInstances.value[question.number] = chart;
};

const renderCharts = () => {
  nextTick(() => {
    paginatedQuestions.value.forEach(question => {
      createQuestionChart(question);
    });
  });
};

// Watch for changes in pagination or sorting
watch([currentPage, itemsPerPage, sortBy], () => {
  renderCharts();
});

watch(() => props.questionsData, () => {
  renderCharts();
}, { deep: true });

onMounted(() => {
  renderCharts();
});
</script>
