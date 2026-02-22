<template>
  <div class="space-y-8">
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-red-100 rounded-lg">
          <ChartBarIcon class="w-6 h-6 text-red-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Etapas - Referencia I (ATS)</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Identificar, analizar, revisar participantes y prevenir por instrumento</p>
    </div>

    <div class="border-b border-slate-200">
      <nav class="-mb-px flex gap-6">
        <button
          v-for="subTab in subTabs"
          :key="subTab.key"
          @click="activeSubTab = subTab.key"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeSubTab === subTab.key
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <component :is="subTab.icon" class="w-5 h-5" />
            {{ subTab.label }}
          </div>
        </button>
      </nav>
    </div>

    <div v-if="activeSubTab === 'identificar'" class="space-y-6">
      <div class="bg-white rounded-lg p-4 border border-slate-200">
        <div class="flex items-center gap-4">
          <span class="text-sm font-medium text-slate-700">Vista:</span>
          <div class="flex gap-2">
            <button
              @click="identifyViewMode = 'blocks'"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                identifyViewMode === 'blocks' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              Bloques
            </button>
            <button
              @click="identifyViewMode = 'questions'"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                identifyViewMode === 'questions' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              Preguntas
            </button>
          </div>
        </div>
      </div>

      <div v-if="identifyViewMode === 'blocks'" class="bg-white rounded-lg p-6">
        <BlocksCharts
          :blocks-data="refIBlocksForCharts"
          :total-evaluations="blockStatistics.total_evaluations"
          :binaryMode="true"
        />
      </div>

      <div v-else class="bg-white rounded-lg p-6">
        <QuestionsCharts
          :questions-data="refIQuestionsForCharts"
          :total-evaluations="questionStatistics.total_evaluations"
          :binaryMode="true"
        />
      </div>
    </div>

    <div v-if="activeSubTab === 'analizar'" class="space-y-6">
      <AnalysisFilters :demographics="analysisData.demographics" v-model="analysisFilters" />

      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-col gap-4">
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Análisis de respuestas (Ref I)</h3>
              <p class="text-sm text-slate-600 mt-1">Visualización basada en filtros demográficos, sin niveles de riesgo</p>
            </div>
            <p class="text-sm text-slate-600">
              <span class="font-semibold">{{ filteredEvaluations.length }}</span> evaluaciones filtradas
            </p>
          </div>

          <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-slate-700">Tipo de gráfica:</span>
            <div class="flex gap-2">
              <button
                @click="chartType = 'pie'"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-2',
                  chartType === 'pie' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Pastel
              </button>
              <button
                @click="chartType = 'bar'"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-2',
                  chartType === 'bar' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Barras
              </button>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <label for="question-filter" class="text-sm font-medium text-slate-700">Pregunta:</label>
            <select
              id="question-filter"
              v-model="selectedQuestionKey"
              class="flex-1 max-w-3xl rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            >
              <option value="">Todas</option>
              <option
                v-for="question in questionStatistics.questions"
                :key="question.key"
                :value="question.key"
              >
                {{ `Pregunta ${question.number} - ${question.text}` }}
              </option>
            </select>
          </div>

          <div v-if="filteredEvaluations.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-3">
              <div class="rounded-lg border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total respuestas</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ responseSummary.totalResponses }}</p>
              </div>
              <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs uppercase tracking-wide text-emerald-700">Respuestas Sí</p>
                <p class="mt-1 text-2xl font-bold text-emerald-800">{{ responseSummary.yesCount }}</p>
                <p class="mt-1 text-xs text-emerald-700">{{ responseSummary.yesPercentage }}%</p>
              </div>
              <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                <p class="text-xs uppercase tracking-wide text-rose-700">Respuestas No</p>
                <p class="mt-1 text-2xl font-bold text-rose-800">{{ responseSummary.noCount }}</p>
                <p class="mt-1 text-xs text-rose-700">{{ responseSummary.noPercentage }}%</p>
              </div>
            </div>

            <div class="lg:col-span-2 rounded-lg border border-slate-200 p-4">
              <canvas ref="analysisChartRef" class="w-full" style="height: 320px"></canvas>
            </div>
          </div>

          <div v-else class="rounded-lg border-2 border-dashed border-slate-300 p-8 text-center">
            <p class="text-sm font-medium text-slate-700">Sin datos para los filtros seleccionados</p>
            <p class="text-xs text-slate-500 mt-1">Ajusta los filtros para visualizar resultados de análisis</p>
          </div>

          <AnalysisWysiwygBlocks
            v-if="organizationId && (canManageAnalysisBlocks || analysisBlocks.referencia_i.length > 0)"
            :organization-id="organizationId"
            instrument-type="referencia_i"
            :blocks="analysisBlocks.referencia_i"
            :can-manage="canManageAnalysisBlocks"
          />
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'participantes'" class="space-y-6">
      <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-8 border border-teal-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-teal-100 rounded-lg">
            <UserGroupIcon class="w-6 h-6 text-teal-600" />
          </div>
          <h3 class="text-2xl font-bold text-teal-900">Informe de Participantes</h3>
        </div>

        <div v-if="filteredEvaluations.length > 0" class="space-y-6">
          <div class="bg-white rounded-lg p-4 border border-slate-200">
            <div class="text-sm text-slate-700">
              <span class="font-medium">Total de participantes:</span>
              <span class="font-bold text-teal-600 ml-2">{{ filteredEvaluations.length }}</span>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <ul class="divide-y divide-slate-200">
              <li
                v-for="(evaluation, index) in filteredEvaluations"
                :key="evaluation.id"
                class="hover:bg-slate-50 transition-colors duration-150 p-4"
              >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="flex items-center gap-3">
                    <div class="bg-teal-100 text-teal-800 font-bold rounded-full h-8 w-8 flex items-center justify-center">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <p class="font-medium text-slate-900">Folio {{ evaluation.personal_folio }}</p>
                      <p class="text-xs text-slate-500 mt-1">
                        {{ evaluation.demographics.genero }} · {{ evaluation.demographics.puesto }} · {{ evaluation.demographics.area }} · {{ evaluation.demographics.turno }}
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center gap-3">
                    <div class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold min-w-[94px] text-center">
                      Sí: {{ evaluation.yes_count }} / 14
                    </div>
                    <span
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                      :style="{
                        color: '#111827',
                        backgroundColor: `${analysisData.colors[evaluation.risk_level] ?? '#94A3B8'}33`
                      }"
                    >
                      ATS: {{ analysisData.labels[evaluation.risk_level] ?? evaluation.risk_level }}
                    </span>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <div v-else class="bg-white rounded-lg p-6">
          <div class="flex items-center justify-center p-8 border-2 border-dashed border-teal-300 rounded-lg">
            <div class="text-center">
              <UserGroupIcon class="w-12 h-12 text-teal-400 mx-auto mb-3" />
              <p class="text-teal-700 font-medium">Sin datos disponibles</p>
              <p class="text-sm text-teal-600 mt-1">No se han encontrado evaluaciones de participantes para mostrar</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'prevenir'" class="space-y-6">
      <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-emerald-100 rounded-lg">
            <ShieldCheckIcon class="w-6 h-6 text-emerald-600" />
          </div>
          <h3 class="text-2xl font-bold text-emerald-900">Prevenir y Controlar ATS</h3>
        </div>
        <div class="bg-white rounded-lg p-6 space-y-4">
          <form
            v-if="canManagePreventionActions && workCenterId"
            @submit.prevent="submitPreventionAction"
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 space-y-4"
          >
            <h4 class="text-sm font-semibold text-emerald-900">Agregar acción preventiva ATS</h4>

            <div>
              <label for="ref1_prevent_title" class="block text-sm font-medium text-slate-700">Título</label>
              <input
                id="ref1_prevent_title"
                v-model="preventionForm.title"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
              />
              <p v-if="preventionForm.errors.title" class="mt-1 text-xs text-red-500">{{ preventionForm.errors.title }}</p>
            </div>

            <div>
              <label for="ref1_prevent_desc" class="block text-sm font-medium text-slate-700">Descripción</label>
              <textarea
                id="ref1_prevent_desc"
                v-model="preventionForm.description"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="ref1_prevent_responsible" class="block text-sm font-medium text-slate-700">Responsable</label>
                <input
                  id="ref1_prevent_responsible"
                  v-model="preventionForm.responsible"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                />
              </div>

              <div>
                <label for="ref1_prevent_due" class="block text-sm font-medium text-slate-700">Fecha objetivo</label>
                <input
                  id="ref1_prevent_due"
                  v-model="preventionForm.due_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                />
              </div>

              <div>
                <label for="ref1_prevent_status" class="block text-sm font-medium text-slate-700">Estatus</label>
                <select
                  id="ref1_prevent_status"
                  v-model="preventionForm.status"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                >
                  <option value="pendiente">Pendiente</option>
                  <option value="en_proceso">En proceso</option>
                  <option value="completada">Completada</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="preventionForm.processing"
                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
              >
                {{ preventionForm.processing ? 'Guardando...' : 'Guardar acción' }}
              </button>
            </div>
          </form>

          <div v-if="preventionActions.length === 0" class="flex items-center justify-center p-8 border-2 border-dashed border-emerald-300 rounded-lg">
            <div class="text-center">
              <PencilSquareIcon class="w-12 h-12 text-emerald-400 mx-auto mb-3" />
              <p class="text-emerald-700 font-medium">Gestión operativa T&amp;MS</p>
              <p class="text-sm text-emerald-600 mt-1">El equipo de T&amp;MS documenta, actualiza y da seguimiento al plan de prevención</p>
            </div>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <article
              v-for="action in preventionActions"
              :key="action.id"
              class="rounded-lg border border-slate-200 p-4 space-y-2"
            >
              <div class="flex items-start justify-between gap-3">
                <h4 class="font-semibold text-slate-900">{{ action.title }}</h4>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClasses[action.status]">
                  {{ statusLabels[action.status] ?? action.status }}
                </span>
              </div>

              <p v-if="action.description" class="text-sm text-slate-600">{{ action.description }}</p>

              <div class="text-xs text-slate-500 flex flex-wrap gap-4">
                <span v-if="action.responsible">Responsable: {{ action.responsible }}</span>
                <span v-if="action.due_date">Fecha: {{ action.due_date }}</span>
              </div>

              <div v-if="canManagePreventionActions && workCenterId" class="flex justify-end">
                <button
                  type="button"
                  @click="deletePreventionAction(action.id)"
                  class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
                >
                  Eliminar
                </button>
              </div>
            </article>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4">
            <LightBulbIcon class="w-6 h-6 text-emerald-600" />
            <h4 class="font-bold text-slate-900">Medidas Preventivas</h4>
          </div>
          <p class="text-sm text-slate-600">Definición de acciones para atención de ATS identificados en respuestas Sí/No.</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4">
            <ArrowPathIcon class="w-6 h-6 text-emerald-600" />
            <h4 class="font-bold text-slate-900">Seguimiento</h4>
          </div>
          <p class="text-sm text-slate-600">Control periódico del avance de acciones registradas por el equipo de T&amp;MS.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AnalysisFilters from './Charts/AnalysisFilters.vue';
import QuestionsCharts from './Charts/QuestionsCharts.vue';
import BlocksCharts from './Charts/BlocksCharts.vue';
import AnalysisWysiwygBlocks from './AnalysisWysiwygBlocks.vue';
import { Chart, registerables } from 'chart.js';
import {
  ArrowPathIcon,
  ChartBarIcon,
  LightBulbIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';

Chart.register(...registerables);

interface RefIQuestionStatistic {
  key: string;
  number: number;
  text: string;
  yes_count: number;
  no_count: number;
  total_responses: number;
  yes_percentage: number;
}

interface QuestionChartData {
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

interface RefIBlockStatistic {
  name: string;
  question_numbers: number[];
  question_count: number;
  yes_count: number;
  no_count: number;
  total_responses: number;
  yes_percentage: number;
}

interface BlockChartData {
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

interface RefIEvaluation {
  id: string;
  personal_folio: string;
  demographics: {
    genero: string;
    puesto: string;
    area: string;
    turno: string;
  };
  answers?: Record<string, unknown>;
  yes_count: number;
  risk_level: string;
}

interface Props {
  analysisData: {
    evaluations: RefIEvaluation[];
    demographics: {
      generos: string[];
      puestos: string[];
      areas: string[];
      turnos: string[];
    };
    colors: Record<string, string>;
    labels: Record<string, string>;
  };
  questionStatistics: {
    questions: RefIQuestionStatistic[];
    total_evaluations: number;
  };
  blockStatistics: {
    blocks: RefIBlockStatistic[];
    total_evaluations: number;
  };
  preventionActions?: Array<{
    id: number;
    title: string;
    description: string | null;
    responsible: string | null;
    status: string;
    due_date: string | null;
  }>;
  canManagePreventionActions?: boolean;
  workCenterId?: string;
  organizationId?: string | number;
  analysisBlocks?: {
    referencia_i: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
    referencia_iii: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
  };
  canManageAnalysisBlocks?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  preventionActions: () => [],
  canManagePreventionActions: false,
  workCenterId: undefined,
  organizationId: undefined,
  analysisBlocks: () => ({ referencia_i: [], referencia_iii: [] }),
  canManageAnalysisBlocks: false,
});

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const preventionForm = useForm({
  instrument_type: 'referencia_i',
  title: '',
  description: '',
  responsible: '',
  status: 'pendiente',
  due_date: '',
  sort_order: 0,
});

const statusLabels: Record<string, string> = {
  pendiente: 'Pendiente',
  en_proceso: 'En proceso',
  completada: 'Completada',
};

const statusClasses: Record<string, string> = {
  pendiente: 'bg-yellow-100 text-yellow-800',
  en_proceso: 'bg-blue-100 text-blue-800',
  completada: 'bg-emerald-100 text-emerald-800',
};

const submitPreventionAction = (): void => {
  if (!props.workCenterId) {
    return;
  }

  preventionForm.post(route('work-centers.prevention-actions.store', props.workCenterId), {
    preserveScroll: true,
    onSuccess: () => {
      preventionForm.reset();
      preventionForm.instrument_type = 'referencia_i';
      preventionForm.status = 'pendiente';
      preventionForm.sort_order = 0;
    },
  });
};

const deletePreventionAction = (actionId: number): void => {
  if (!props.workCenterId) {
    return;
  }

  router.delete(route('work-centers.prevention-actions.destroy', [props.workCenterId, actionId]), {
    preserveScroll: true,
  });
};

const activeSubTab = ref<'identificar' | 'analizar' | 'participantes' | 'prevenir'>('identificar');
const identifyViewMode = ref<'blocks' | 'questions'>('blocks');
const chartType = ref<'pie' | 'bar'>('pie');
const selectedQuestionKey = ref('');
const analysisFilters = ref({
  genero: '',
  puesto: '',
  area: '',
  turno: '',
});
const analysisChartRef = ref<HTMLCanvasElement | null>(null);
const analysisChartInstance = ref<Chart | null>(null);

const filteredEvaluations = computed(() => {
  return props.analysisData.evaluations.filter((evaluation) => {
    if (analysisFilters.value.genero && evaluation.demographics.genero !== analysisFilters.value.genero) {
      return false;
    }
    if (analysisFilters.value.puesto && evaluation.demographics.puesto !== analysisFilters.value.puesto) {
      return false;
    }
    if (analysisFilters.value.area && evaluation.demographics.area !== analysisFilters.value.area) {
      return false;
    }
    if (analysisFilters.value.turno && evaluation.demographics.turno !== analysisFilters.value.turno) {
      return false;
    }

    return true;
  });
});

const responseSummary = computed(() => {
  const participants = filteredEvaluations.value.length;

  if (selectedQuestionKey.value) {
    let totalResponses = 0;
    let yesCount = 0;

    filteredEvaluations.value.forEach((evaluation) => {
      const answer = evaluation.answers?.[selectedQuestionKey.value];

      if (answer === null || answer === undefined) {
        return;
      }

      totalResponses++;
      if (isAffirmativeAnswer(answer)) {
        yesCount++;
      }
    });

    const noCount = Math.max(totalResponses - yesCount, 0);

    return {
      totalResponses,
      yesCount,
      noCount,
      yesPercentage: totalResponses > 0 ? Number(((yesCount / totalResponses) * 100).toFixed(2)) : 0,
      noPercentage: totalResponses > 0 ? Number(((noCount / totalResponses) * 100).toFixed(2)) : 0,
    };
  }

  const totalResponses = participants * 14;
  const yesCount = filteredEvaluations.value.reduce((total, evaluation) => total + (evaluation.yes_count ?? 0), 0);
  const noCount = Math.max(totalResponses - yesCount, 0);

  return {
    totalResponses,
    yesCount,
    noCount,
    yesPercentage: totalResponses > 0 ? Number(((yesCount / totalResponses) * 100).toFixed(2)) : 0,
    noPercentage: totalResponses > 0 ? Number(((noCount / totalResponses) * 100).toFixed(2)) : 0,
  };
});

const refIQuestionsForCharts = computed(() => {
  const mappedQuestions = props.questionStatistics.questions.map<QuestionChartData>((question) => {
    const negativePercentage = question.total_responses > 0
      ? Number(((question.no_count / question.total_responses) * 100).toFixed(2))
      : 0;

    let criticality: 'low' | 'medium' | 'high' | 'critical' = 'low';
    if (negativePercentage >= 50) {
      criticality = 'critical';
    } else if (negativePercentage >= 30) {
      criticality = 'high';
    } else if (negativePercentage >= 15) {
      criticality = 'medium';
    }

    return {
      number: question.number,
      text: question.text,
      category: 'Referencia I',
      domain: 'ATS',
      dimension: 'Acontecimientos Traumáticos Severos',
      responses: {
        siempre: question.yes_count,
        casi_siempre: 0,
        algunas_veces: 0,
        casi_nunca: 0,
        nunca: question.no_count,
      },
      averageScore: Number((question.yes_percentage / 25).toFixed(2)),
      criticality,
    };
  });

  return mappedQuestions.reduce<Record<string, QuestionChartData>>((accumulator, question) => {
    accumulator[String(question.number)] = question;
    return accumulator;
  }, {});
});

const refIBlocksForCharts = computed(() => {
  const mappedBlocks = props.blockStatistics.blocks.map<BlockChartData>((block, index) => {
    const negativePercentage = block.total_responses > 0
      ? Number(((block.no_count / block.total_responses) * 100).toFixed(2))
      : 0;

    let criticality: 'low' | 'medium' | 'high' | 'critical' = 'low';
    if (negativePercentage >= 50) {
      criticality = 'critical';
    } else if (negativePercentage >= 30) {
      criticality = 'high';
    } else if (negativePercentage >= 15) {
      criticality = 'medium';
    }

    return {
      block_number: index + 1,
      instructions: block.name,
      question_count: block.question_count,
      questions: block.question_numbers,
      responses: {
        siempre: block.yes_count,
        casi_siempre: 0,
        algunas_veces: 0,
        casi_nunca: 0,
        nunca: block.no_count,
      },
      total_responses: block.total_responses,
      average_score: Number((block.yes_percentage / 25).toFixed(2)),
      negative_percentage: negativePercentage,
      criticality,
    };
  });

  return mappedBlocks.reduce<Record<string, BlockChartData>>((accumulator, block) => {
    accumulator[String(block.block_number)] = block;
    return accumulator;
  }, {});
});

const isAffirmativeAnswer = (answer: unknown): boolean => {
  if (typeof answer === 'string') {
    const normalizedAnswer = answer.trim().toLowerCase();
    return ['sí', 'si', 'true', '1'].includes(normalizedAnswer);
  }

  return answer === true || answer === 1;
};

const destroyAnalysisChart = (): void => {
  if (analysisChartInstance.value) {
    analysisChartInstance.value.destroy();
    analysisChartInstance.value = null;
  }
};

const renderAnalysisChart = async (): Promise<void> => {
  await nextTick();

  if (activeSubTab.value !== 'analizar' || filteredEvaluations.value.length === 0 || !analysisChartRef.value) {
    destroyAnalysisChart();
    return;
  }

  const context = analysisChartRef.value.getContext('2d');
  if (!context) {
    return;
  }

  destroyAnalysisChart();

  analysisChartInstance.value = new Chart(context, {
    type: chartType.value,
    data: {
      labels: ['Sí', 'No'],
      datasets: [
        {
          label: 'Respuestas',
          data: [responseSummary.value.yesCount, responseSummary.value.noCount],
          backgroundColor: ['#10B981', '#EF4444'],
          borderColor: ['#059669', '#DC2626'],
          borderWidth: 1,
          borderRadius: chartType.value === 'bar' ? 8 : 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
        },
        tooltip: {
          callbacks: {
            label: (tooltipItem) => {
              const value = tooltipItem.parsed as number;
              const percentage = responseSummary.value.totalResponses > 0
                ? ((value / responseSummary.value.totalResponses) * 100).toFixed(2)
                : '0.00';

              return `${value} (${percentage}%)`;
            },
          },
        },
      },
      scales: chartType.value === 'bar'
        ? {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
            },
            x: {
              grid: {
                display: false,
              },
            },
          }
        : {},
    },
  });
};

const subTabs = [
  { key: 'identificar', label: 'Identificar', icon: MagnifyingGlassIcon },
  { key: 'analizar', label: 'Analizar', icon: ChartBarIcon },
  { key: 'participantes', label: 'Participantes', icon: UserGroupIcon },
  { key: 'prevenir', label: 'Prevenir', icon: ShieldCheckIcon },
] as const;

watch([activeSubTab, chartType, filteredEvaluations, selectedQuestionKey], () => {
  renderAnalysisChart();
}, { deep: true });

onMounted(() => {
  renderAnalysisChart();
});
</script>
