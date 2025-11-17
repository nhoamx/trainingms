<template>
  <Dashboard :title="title || 'Reporte Clima Laboral'">
    <div class="max-w-7xl mx-auto p-6">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">
          Reporte Clima Laboral - {{ organizationName }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          {{ evaluations.length }} evaluaciones completadas
        </p>
      </div>

      <div v-if="evaluations.length === 0" class="bg-white rounded-lg shadow p-8">
        <div class="text-center text-gray-500">
          <p class="text-lg">No hay evaluaciones Clima Laboral completadas para esta organización.</p>
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
              <!-- Clima Laboral -->
              <div class="mb-6 rounded-lg p-6" :class="getLevelColor(getMostCommonInterpretation).bgSolid">
                <h3 class="text-lg font-semibold mb-2" :class="getLevelColor(getMostCommonInterpretation).text">Clima Laboral</h3>
                <div class="flex items-baseline gap-3">
                  <span class="text-3xl font-bold" :class="getLevelColor(getMostCommonInterpretation).text">{{ getMostCommonInterpretation }}</span>
                  <span class="text-lg opacity-90" :class="getLevelColor(getMostCommonInterpretation).text">/ {{ filteredTotalPeople }} {{ filteredTotalPeople === 1 ? 'persona' : 'personas' }}</span>
                </div>
                <div class="text-sm mt-2 opacity-90" :class="getLevelColor(getMostCommonInterpretation).text">
                  Nivel más frecuente en la organización
                </div>
              </div>

              <!-- Gráfica de Pastel - Distribución por Nivel de Clima Laboral -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Nivel de Satisfacción</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <canvas ref="pieChartTotal"></canvas>
                </div>
              </div>

              <!-- Lista de Dimensiones con Distribución de Personas -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Distribución de Personas por Dimensión</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div 
                    v-for="(dim, dimName) in filteredDimensions" 
                    :key="dimName"
                    class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500"
                  >
                    <div class="mb-3">
                      <span class="font-medium text-gray-900">{{ dimName }}</span>
                      <div class="text-xs text-gray-500 mt-1">
                        {{ dim.questionCount }} preguntas
                      </div>
                    </div>
                    <div class="space-y-2 text-sm">
                      <div v-for="(count, level) in dim.distribution" :key="level" class="flex items-center justify-between gap-2">
                        <span class="px-2 py-1 rounded text-xs font-medium flex-shrink-0" :class="getLevelColor(level).badge">
                          {{ level }}
                        </span>
                        <span class="font-medium text-gray-900">{{ count }} {{ count === 1 ? 'persona' : 'personas' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mapa de Calor - Todas las Preguntas -->
              <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Mapa de Calor - Todas las Preguntas</h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-300">
                    <thead>
                      <!-- Dimension headers row -->
                      <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 sticky left-0 bg-gray-100 z-10">
                          Folio
                        </th>
                        <template v-for="(dim, dimName) in filteredDimensions" :key="`dim-header-${dimName}`">
                          <th 
                            :colspan="dim.questionCount" 
                            class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 text-center"
                          >
                            {{ dimName }}
                          </th>
                        </template>
                      </tr>
                      <!-- Question numbers row -->
                      <tr class="bg-gray-50">
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10">
                          #
                        </th>
                        <template v-for="(dim, dimName) in filteredDimensions" :key="`questions-${dimName}`">
                          <th 
                            v-for="qNum in Object.keys(dim.questions)" 
                            :key="`q-header-${qNum}`"
                            class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 text-center"
                          >
                            {{ qNum }}
                          </th>
                        </template>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="evaluation in filteredEvaluations" :key="`eval-${evaluation.folio}`">
                        <!-- Personal folio column -->
                        <td class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-900 sticky left-0 bg-white z-10">
                          {{ evaluation.personal_folio }}
                        </td>
                        <!-- Answer cells for each question -->
                        <template v-for="(dim, dimName) in filteredDimensions" :key="`eval-dim-${evaluation.folio}-${dimName}`">
                          <td 
                            v-for="qNum in Object.keys(dim.questions)" 
                            :key="`eval-q-${evaluation.folio}-${qNum}`"
                            class="border border-gray-300 px-2 py-2 text-center text-xs font-bold"
                            :class="getAnswerColorClass(evaluation.answers[qNum])"
                          >
                            {{ getAnswerNumericValue(evaluation.answers[qNum]) }}
                          </td>
                        </template>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Dimension Tabs -->
            <div v-else-if="filteredDimensions[activeTab]">
              <!-- Distribución de la Dimensión -->
              <div class="mb-6 bg-gray-50 rounded-lg p-6 border-2 border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ activeTab }}</h3>
                <div class="text-sm text-gray-600 mb-3">
                  {{ filteredDimensions[activeTab].questionCount }} preguntas evaluadas
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                  <div v-for="(count, level) in filteredDimensions[activeTab].distribution" :key="level" class="text-center p-3 rounded-lg" :class="getLevelColor(level).bgSolid">
                    <div class="text-2xl font-bold" :class="getLevelColor(level).text">{{ count }}</div>
                    <div class="text-xs mt-1" :class="getLevelColor(level).text">{{ level }}</div>
                  </div>
                </div>
              </div>

              <!-- Gráfica de Pastel - Distribución de Personas por Nivel -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Distribución de Personas por Nivel (%)</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <canvas :ref="el => dimensionChartRefs[activeTab] = el"></canvas>
                </div>
              </div>

              <!-- Mapa de Calor para esta Dimensión -->
              <div>
                <h4 class="text-md font-semibold text-gray-900 mb-4">Mapa de Calor - {{ activeTab }}</h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-300">
                    <thead>
                      <!-- Question numbers row -->
                      <tr class="bg-gray-50">
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10">
                          Folio
                        </th>
                        <th 
                          v-for="qNum in Object.keys(filteredDimensions[activeTab]?.questions || {})" 
                          :key="`dim-q-header-${qNum}`"
                          class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 text-center"
                        >
                          {{ qNum }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="evaluation in filteredEvaluations" :key="`dim-eval-${evaluation.folio}`">
                        <!-- Personal folio column -->
                        <td class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-900 sticky left-0 bg-white z-10">
                          {{ evaluation.personal_folio }}
                        </td>
                        <!-- Answer cells for this dimension's questions -->
                        <td 
                          v-for="qNum in Object.keys(filteredDimensions[activeTab]?.questions || {})" 
                          :key="`dim-eval-q-${evaluation.folio}-${qNum}`"
                          class="border border-gray-300 px-2 py-2 text-center text-xs font-bold"
                          :class="getAnswerColorClass(evaluation.answers[qNum])"
                        >
                          {{ getAnswerNumericValue(evaluation.answers[qNum]) }}
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
import { ref, computed, onMounted, watch, nextTick } from 'vue'
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
    default: 'Reporte Clima Laboral',
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
  climaLaboralDistribution: {
    type: Object,
    default: () => ({
      'Totalmente de Acuerdo': 0,
      'De Acuerdo': 0,
      'Desacuerdo': 0,
      'Totalmente Desacuerdo': 0,
    }),
  },
  totalPeople: {
    type: Number,
    default: 0,
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
const dimensionChartRefs = ref({})
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

// Recompute dimensions distribution based on filtered evaluations
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
    const questionScores = {}

    // Distribution of people by level for this dimension (filtered)
    const dimensionDistribution = {
      'Totalmente de Acuerdo': 0,
      'De Acuerdo': 0,
      'Desacuerdo': 0,
      'Totalmente Desacuerdo': 0,
    }

    // Get dimension level ranges from config
    const dimensionRanges = getLevelRanges(dimensionName)

    // Calculate score for each person in this dimension
    filteredEvaluations.value.forEach(evalData => {
      let personScore = 0
      questionNumbers.forEach(qNum => {
        const answer = evalData.answers[qNum]
        if (answer) {
          personScore += valorOpciones[answer] || 0
        }
      })

      // Get interpretation for this person's dimension score
      const interpretation = getScoreInterpretation(personScore, dimensionRanges)
      if (interpretation) {
        dimensionDistribution[interpretation] = (dimensionDistribution[interpretation] || 0) + 1
      }
    })

    // Calculate average scores per question for display
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
    })

    dimensionSummaries[dimensionName] = {
      name: dimensionName,
      distribution: dimensionDistribution,
      questionCount: questionNumbers.length,
      questions: questionScores,
    }
  })

  return dimensionSummaries
})

// Calculate Clima Laboral distribution for filtered evaluations
const filteredClimaLaboralDistribution = computed(() => {
  const distribution = {
    'Totalmente de Acuerdo': 0,
    'De Acuerdo': 0,
    'Desacuerdo': 0,
    'Totalmente Desacuerdo': 0,
  }

  filteredEvaluations.value.forEach(evalData => {
    const interpretation = evalData.scores?.interpretation
    if (interpretation) {
      distribution[interpretation] = (distribution[interpretation] || 0) + 1
    }
  })

  return distribution
})

const filteredTotalPeople = computed(() => {
  return filteredEvaluations.value.length
})

// Get most common interpretation (modal)
const getMostCommonInterpretation = computed(() => {
  const dist = filteredClimaLaboralDistribution.value
  let maxCount = 0
  let mostCommon = 'Sin datos'

  Object.entries(dist).forEach(([level, count]) => {
    if (count > maxCount) {
      maxCount = count
      mostCommon = level
    }
  })

  return mostCommon
})

// Helper function to get level ranges for a dimension
const getLevelRanges = (dimensionName) => {
  // These ranges should match the config in likert-value.php
  const ranges = {
    'Entorno Laboral Seguro': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Seguridad Laboral': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Compensación Justa': [
      { min: 3.26, max: 4, level: 'Totalmente de Acuerdo' },
      { min: 2.6, max: 3.25, level: 'De Acuerdo' },
      { min: 1.76, max: 2.5, level: 'Desacuerdo' },
      { min: 1, max: 1.75, level: 'Totalmente Desacuerdo' },
    ],
    'Comunicación Abierta': [
      { min: 19.6, max: 24, level: 'Totalmente de Acuerdo' },
      { min: 15.1, max: 19.5, level: 'De Acuerdo' },
      { min: 10.6, max: 15, level: 'Desacuerdo' },
      { min: 6, max: 10.5, level: 'Totalmente Desacuerdo' },
    ],
    'Participación de los Empleados': [
      { min: 9.76, max: 12, level: 'Totalmente de Acuerdo' },
      { min: 7.6, max: 9.75, level: 'De Acuerdo' },
      { min: 5.26, max: 7.5, level: 'Desacuerdo' },
      { min: 3, max: 5.25, level: 'Totalmente Desacuerdo' },
    ],
    'Reconocimiento y Recompensa': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Capacitación y Desarrollo': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Equilibrio entre Vida Laboral y Personal': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Avance Profesional': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Apoyo al Empleado': [
      { min: 3.26, max: 4, level: 'Totalmente de Acuerdo' },
      { min: 2.6, max: 3.25, level: 'De Acuerdo' },
      { min: 1.76, max: 2.5, level: 'Desacuerdo' },
      { min: 1, max: 1.75, level: 'Totalmente Desacuerdo' },
    ],
  }

  return ranges[dimensionName] || []
}

// Helper function to get interpretation from score
const getScoreInterpretation = (score, ranges) => {
  for (const range of ranges) {
    if (score >= range.min && score <= range.max) {
      return range.level
    }
  }
  return null
}

const resetFilters = () => {
  filters.value = {
    genero: '',
    tipo_contrato: '',
    puesto: '',
    area: '',
  }
}

// Helper: Convert letter answer to numeric value
const getAnswerNumericValue = (answer) => {
  const valueMap = {
    'A': 4,
    'B': 3,
    'C': 2,
    'D': 1
  }
  return valueMap[answer] || '-'
}

// Helper: Get Tailwind color class for answer value
const getAnswerColorClass = (answer) => {
  const value = getAnswerNumericValue(answer)
  
  // Standardized colors per user specification:
  // 4 (A): Azul cielo (sky blue) - Totalmente de Acuerdo
  // 3 (B): Verde mayate (green) - De Acuerdo
  // 2 (C): Amarillo mostaza (mustard yellow) - Desacuerdo
  // 1 (D): Rojo (red) - Totalmente Desacuerdo
  
  switch(value) {
    case 4:
      return 'bg-blue-400 text-black'  // Azul cielo
    case 3:
      return 'bg-green-600 text-white'  // Verde mayate
    case 2:
      return 'bg-yellow-500 text-black'  // Amarillo mostaza
    case 1:
      return 'bg-red-600 text-white'  // Rojo
    default:
      return 'bg-gray-200 text-gray-500'  // Sin respuesta
  }
}

// Helper: Get standardized color for level (used in charts and badges)
const getLevelColor = (level) => {
  // Same standardized colors as heat map
  const colorMap = {
    'Totalmente de Acuerdo': {
      bg: 'rgba(96, 165, 250, 0.8)',      // Blue-400 with opacity
      bgSolid: 'bg-blue-400',
      text: 'text-black',
      badge: 'bg-blue-400 text-black'
    },
    'De Acuerdo': {
      bg: 'rgba(22, 163, 74, 0.8)',      // Green-600 with opacity
      bgSolid: 'bg-green-600',
      text: 'text-white',
      badge: 'bg-green-600 text-white'
    },
    'Desacuerdo': {
      bg: 'rgba(234, 179, 8, 0.8)',      // Yellow-500 with opacity
      bgSolid: 'bg-yellow-500',
      text: 'text-black',
      badge: 'bg-yellow-500 text-black'
    },
    'Totalmente Desacuerdo': {
      bg: 'rgba(220, 38, 38, 0.8)',      // Red-600 with opacity
      bgSolid: 'bg-red-600',
      text: 'text-white',
      badge: 'bg-red-600 text-white'
    }
  }
  return colorMap[level] || {
    bg: 'rgba(156, 163, 175, 0.8)',
    bgSolid: 'bg-gray-400',
    text: 'text-white',
    badge: 'bg-gray-400 text-white'
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

  // Generate colors based on labels using standardized color scheme
  const backgroundColors = labels.map(label => getLevelColor(label).bg)

  const chart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: backgroundColors,
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
              const personas = value === 1 ? 'persona' : 'personas'
              return `${label}: ${value} ${personas} (${percentage}%)`
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
    // Total pie chart - Distribution by Clima Laboral level
    if (pieChartTotal.value) {
      const distribution = filteredClimaLaboralDistribution.value
      const labels = Object.keys(distribution).filter(key => distribution[key] > 0)
      const data = labels.map(key => distribution[key])
      
      if (data.length > 0) {
        createPieChart(pieChartTotal.value, labels, data, 'Total')
      }
    }

    // Dimension-specific pie charts - Distribution by level for each dimension
    Object.keys(filteredDimensions.value).forEach(dimensionName => {
      const canvasRef = dimensionChartRefs.value[dimensionName]
      if (canvasRef && canvasRef instanceof HTMLCanvasElement) {
        const dimension = filteredDimensions.value[dimensionName]
        const distribution = dimension.distribution
        const labels = Object.keys(distribution).filter(key => distribution[key] > 0)
        const data = labels.map(key => distribution[key])
        
        if (data.length > 0) {
          createPieChart(canvasRef, labels, data, dimensionName)
        }
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
