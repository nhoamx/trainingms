<template>
  <Dashboard :title="title || 'Reporte Likert'">
    <div class="max-w-7xl mx-auto p-6">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">
          Reporte Likert - {{ organizationName }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          {{ evaluations.length }} evaluaciones completadas
        </p>
      </div>

      <div v-if="evaluations.length === 0" class="bg-white rounded-lg shadow p-8">
        <div class="text-center text-gray-500">
          <p class="text-lg">No hay evaluaciones Likert completadas para esta organización.</p>
        </div>
      </div>

      <div v-else>
        <!-- Filtros Demográficos -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Género -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
              <select 
                v-model="filters.genero"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="g in demographics.generos" :key="g" :value="g">{{ g }}</option>
              </select>
            </div>

            <!-- Tipo de Contrato -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato</label>
              <select 
                v-model="filters.tipo_contrato"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="tc in demographics.tipos_contrato" :key="tc" :value="tc">{{ tc }}</option>
              </select>
            </div>

            <!-- Puesto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Puesto</label>
              <select 
                v-model="filters.puesto"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="p in demographics.puestos" :key="p" :value="p">{{ getPuestoName(p) }}</option>
              </select>
            </div>

            <!-- Área -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
              <select 
                v-model="filters.area"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todas</option>
                <option v-for="a in demographics.areas" :key="a" :value="a">{{ getAreaName(a) }}</option>
              </select>
            </div>
          </div>

          <!-- Reset Filters Button -->
          <div class="mt-4">
            <button 
              @click="resetFilters"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm"
            >
              Limpiar Filtros
            </button>
          </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
          <div class="border-b border-gray-200">
            <nav class="flex flex-wrap -mb-px">
              <button
                @click="activeTab = 'Total'"
                :class="[
                  'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                  activeTab === 'Total'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Total
              </button>
              <button
                v-for="dimensionName in Object.keys(dimensions)"
                :key="dimensionName"
                @click="activeTab = dimensionName"
                :class="[
                  'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                  activeTab === dimensionName
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                {{ dimensionName }}
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            <!-- Total Tab -->
            <div v-if="activeTab === 'Total'">
              <!-- Calificación General -->
              <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Calificación General</h3>
                <div class="flex items-baseline gap-3">
                  <span class="text-4xl font-bold text-blue-600">{{ filteredTotalScore.toFixed(2) }}</span>
                  <span class="text-lg text-gray-600">/ 92</span>
                  <span class="ml-4 px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ filteredTotalInterpretation }}
                  </span>
                </div>
              </div>

              <!-- Gráfica de Pastel - Distribución por Dimensión -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Distribución de Puntuación por Dimensión (%)</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <canvas ref="pieChartTotal"></canvas>
                </div>
              </div>

              <!-- Lista de Dimensiones con Puntuaciones -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Puntuación por Dimensión</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div 
                    v-for="(dim, dimName) in filteredDimensions" 
                    :key="dimName"
                    class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500"
                  >
                    <div class="flex justify-between items-start">
                      <span class="font-medium text-gray-900">{{ dimName }}</span>
                      <span class="text-lg font-bold text-blue-600">{{ dim.score.toFixed(2) }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                      {{ dim.questionCount }} preguntas
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mapa de Calor - Todas las Preguntas -->
              <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Mapa de Calor - Todas las Preguntas</h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          #
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Pregunta
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Puntuación Promedio
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Nivel
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <template v-for="(dim, dimName) in filteredDimensions" :key="`dim-${dimName}`">
                        <tr class="bg-gray-100">
                          <td colspan="4" class="px-4 py-2 text-sm font-semibold text-gray-900">
                            {{ dimName }}
                          </td>
                        </tr>
                        <tr 
                          v-for="(q, qNum) in dim.questions" 
                          :key="`q-${dimName}-${qNum}`"
                          :class="getHeatmapColor(q.score)"
                        >
                          <td class="px-4 py-2 text-sm text-gray-900">{{ qNum }}</td>
                          <td class="px-4 py-2 text-sm text-gray-700">{{ q.question }}</td>
                          <td class="px-4 py-2 text-sm font-semibold text-gray-900">{{ q.score.toFixed(2) }}</td>
                          <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 rounded text-xs font-medium" :class="getScoreBadgeClass(q.score)">
                              {{ getScoreLevel(q.score) }}
                            </span>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Dimension Tabs -->
            <div v-else-if="filteredDimensions[activeTab]">
              <!-- Calificación de la Dimensión -->
              <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ activeTab }}</h3>
                <div class="flex items-baseline gap-3">
                  <span class="text-4xl font-bold text-blue-600">
                    {{ filteredDimensions[activeTab].score.toFixed(2) }}
                  </span>
                  <span class="text-lg text-gray-600">
                    / {{ filteredDimensions[activeTab].questionCount * 4 }}
                  </span>
                </div>
                <div class="text-sm text-gray-600 mt-2">
                  {{ filteredDimensions[activeTab].questionCount }} preguntas evaluadas
                </div>
              </div>

              <!-- Gráfica de Pastel - Distribución de Respuestas -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Distribución de Puntuación por Pregunta (%)</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <canvas :ref="`pieChart-${activeTab}`"></canvas>
                </div>
              </div>

              <!-- Lista de Preguntas con Calificación -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Preguntas</h4>
                <div class="space-y-3">
                  <div 
                    v-for="(q, qNum) in filteredDimensions[activeTab].questions" 
                    :key="qNum"
                    class="bg-gray-50 rounded-lg p-4 border-l-4"
                    :class="getQuestionBorderClass(q.score)"
                  >
                    <div class="flex justify-between items-start mb-2">
                      <span class="text-xs font-semibold text-gray-500">Pregunta {{ qNum }}</span>
                      <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-blue-600">{{ q.score.toFixed(2) }}</span>
                        <span class="px-2 py-1 rounded text-xs font-medium" :class="getScoreBadgeClass(q.score)">
                          {{ getScoreLevel(q.score) }}
                        </span>
                      </div>
                    </div>
                    <p class="text-sm text-gray-700">{{ q.question }}</p>
                  </div>
                </div>
              </div>

              <!-- Mapa de Calor para esta Dimensión -->
              <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Mapa de Calor</h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Pregunta
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Puntuación
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Nivel
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr 
                        v-for="(q, qNum) in filteredDimensions[activeTab].questions" 
                        :key="qNum"
                        :class="getHeatmapColor(q.score)"
                      >
                        <td class="px-4 py-3 text-sm text-gray-700">
                          <div class="font-medium text-gray-900 mb-1">Pregunta {{ qNum }}</div>
                          <div class="text-xs">{{ q.question }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-gray-900">
                          {{ q.score.toFixed(2) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                          <span class="px-2 py-1 rounded text-xs font-medium" :class="getScoreBadgeClass(q.score)">
                            {{ getScoreLevel(q.score) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, getCurrentInstance } from 'vue'
import Dashboard from '@/Layouts/Dashboard.vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  organizationId: {
    type: String,
    required: true,
  },
  organizationName: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: 'Reporte Likert',
  },
  evaluations: {
    type: Array,
    default: () => [],
  },
  demographics: {
    type: Object,
    default: () => ({
      generos: [],
      tipos_contrato: [],
      puestos: [],
      areas: [],
    }),
  },
  dimensions: {
    type: Object,
    default: () => ({}),
  },
  totalScore: {
    type: Number,
    default: 0,
  },
  totalInterpretation: {
    type: String,
    default: '',
  },
  puestosMap: {
    type: Object,
    default: () => ({}),
  },
  areasMap: {
    type: Object,
    default: () => ({}),
  },
})

const activeTab = ref('Total')
const filters = ref({
  genero: '',
  tipo_contrato: '',
  puesto: '',
  area: '',
})

const pieChartTotal = ref(null)
const chartInstances = ref({})

// Helper functions to get names
const getPuestoName = (puestoId) => {
  if (!puestoId) return 'Sin Puesto'
  return props.puestosMap[puestoId] || puestoId
}

const getAreaName = (areaId) => {
  if (!areaId) return 'Sin Área'
  return props.areasMap[areaId] || areaId
}

// Filter evaluations based on demographic filters
const filteredEvaluations = computed(() => {
  return props.evaluations.filter(evaluation => {
    if (filters.value.genero && evaluation.demographics.genero !== filters.value.genero) return false
    if (filters.value.tipo_contrato && evaluation.demographics.tipo_contrato !== filters.value.tipo_contrato) return false
    if (filters.value.puesto && evaluation.demographics.puesto !== filters.value.puesto) return false
    if (filters.value.area && evaluation.demographics.area !== filters.value.area) return false
    return true
  })
})

// Recompute dimensions based on filtered evaluations
const filteredDimensions = computed(() => {
  if (filteredEvaluations.value.length === 0 || !props.dimensions || Object.keys(props.dimensions).length === 0) {
    return props.dimensions || {}
  }

  const valorOpciones = { A: 4, B: 3, C: 2, D: 1 }
  const dimensionSummaries = {}

  Object.keys(props.dimensions).forEach(dimensionName => {
    const dimension = props.dimensions[dimensionName]
    if (!dimension || !dimension.questions) return

    const questionNumbers = Object.keys(dimension.questions).map(Number)
    let totalScore = 0
    const questionScores = {}

    questionNumbers.forEach(qNum => {
      let qScore = 0
      let qCount = 0
      filteredEvaluations.value.forEach(evalData => {
        const answer = evalData.answers[qNum]
        if (answer) {
          qScore += valorOpciones[answer] || 0
          qCount++
        }
      })
      const avgScore = qCount > 0 ? qScore / qCount : 0
      const origQuestion = dimension.questions[qNum]
      questionScores[qNum] = {
        question: (typeof origQuestion === 'object' ? origQuestion.question : origQuestion) || `Pregunta ${qNum}`,
        score: avgScore,
      }
      totalScore += avgScore
    })

    dimensionSummaries[dimensionName] = {
      name: dimensionName,
      score: totalScore,
      questionCount: questionNumbers.length,
      questions: questionScores,
    }
  })

  return dimensionSummaries
})

const filteredTotalScore = computed(() => {
  let total = 0
  Object.values(filteredDimensions.value).forEach(dim => {
    total += dim.score
  })
  return total
})

const filteredTotalInterpretation = computed(() => {
  const score = filteredTotalScore.value
  if (score >= 75.6) return 'Totalmente de Acuerdo'
  if (score >= 59) return 'De Acuerdo'
  if (score >= 40.6) return 'Desacuerdo'
  return 'Totalmente Desacuerdo'
})

const resetFilters = () => {
  filters.value = {
    genero: '',
    tipo_contrato: '',
    puesto: '',
    area: '',
  }
}

const getHeatmapColor = (score) => {
  if (score >= 3.5) return 'bg-green-100'
  if (score >= 2.5) return 'bg-yellow-100'
  if (score >= 1.5) return 'bg-orange-100'
  return 'bg-red-100'
}

const getQuestionBorderClass = (score) => {
  if (score >= 3.5) return 'border-green-500'
  if (score >= 2.5) return 'border-yellow-500'
  if (score >= 1.5) return 'border-orange-500'
  return 'border-red-500'
}

const getScoreBadgeClass = (score) => {
  if (score >= 3.5) return 'bg-green-100 text-green-800'
  if (score >= 2.5) return 'bg-yellow-100 text-yellow-800'
  if (score >= 1.5) return 'bg-orange-100 text-orange-800'
  return 'bg-red-100 text-red-800'
}

const getScoreLevel = (score) => {
  if (score >= 3.5) return 'Totalmente de Acuerdo'
  if (score >= 2.5) return 'De Acuerdo'
  if (score >= 1.5) return 'Desacuerdo'
  return 'Totalmente Desacuerdo'
}

const createPieChart = (canvasRef, labels, data, title) => {
  if (!canvasRef) return

  const ctx = canvasRef.getContext('2d')
  
  // Destroy existing chart if any
  const existingChart = chartInstances.value[title]
  if (existingChart) {
    existingChart.destroy()
  }

  const chart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(139, 92, 246, 0.8)',
          'rgba(236, 72, 153, 0.8)',
          'rgba(20, 184, 166, 0.8)',
          'rgba(251, 146, 60, 0.8)',
          'rgba(99, 102, 241, 0.8)',
          'rgba(234, 179, 8, 0.8)',
        ],
        borderWidth: 2,
        borderColor: '#fff',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            boxWidth: 12,
            padding: 10,
          }
        },
        title: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || ''
              const value = context.parsed || 0
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percentage = ((value / total) * 100).toFixed(1)
              return `${label}: ${percentage}%`
            }
          }
        }
      }
    }
  })

  chartInstances.value[title] = chart
}

const renderCharts = () => {
  nextTick(() => {
    // Total pie chart
    if (pieChartTotal.value) {
      const labels = Object.keys(filteredDimensions.value)
      const data = labels.map(key => filteredDimensions.value[key].score)
      createPieChart(pieChartTotal.value, labels, data, 'Total')
    }

    // Dimension-specific pie charts
    Object.keys(filteredDimensions.value).forEach(dimensionName => {
      const refKey = `pieChart-${dimensionName}`
      const canvasRef = getCurrentInstance()?.refs[refKey]
      if (canvasRef && canvasRef instanceof HTMLCanvasElement) {
        const dimension = filteredDimensions.value[dimensionName]
        const labels = Object.keys(dimension.questions).map(qNum => `P${qNum}`)
        const data = Object.values(dimension.questions).map(q => q.score)
        createPieChart(canvasRef, labels, data, dimensionName)
      }
    })
  })
}

onMounted(() => {
  renderCharts()
})

watch([activeTab, filteredDimensions], () => {
  renderCharts()
}, { deep: true })
</script>
