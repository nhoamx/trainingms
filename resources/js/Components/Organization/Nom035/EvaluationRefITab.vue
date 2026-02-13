<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-red-100 rounded-lg">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Evaluación Referencia I (ATS)</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">
        Identificación de trabajadores expuestos a acontecimientos traumáticos severos
      </p>
    </div>

    <!-- Resumen Ejecutivo -->
    <div v-if="executiveSummary" class="bg-gradient-to-r from-red-50 to-orange-50 rounded-xl p-8 border border-red-200">
      <h3 class="text-2xl font-bold text-red-900 mb-6">Resumen Ejecutivo</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg p-6 border-l-4 border-red-500 shadow-sm">
          <p class="text-sm text-slate-600 mb-1">Total Participantes</p>
          <p class="text-3xl font-bold text-slate-900">{{ executiveSummary.total_participants }}</p>
        </div>
        <div class="bg-white rounded-lg p-6 border-l-4 border-orange-500 shadow-sm">
          <p class="text-sm text-slate-600 mb-1">Total Preguntas</p>
          <p class="text-3xl font-bold text-slate-900">{{ executiveSummary.total_questions }}</p>
        </div>
        <div class="bg-white rounded-lg p-6 border-l-4 border-amber-500 shadow-sm">
          <p class="text-sm text-slate-600 mb-1">Tipo de Evaluación</p>
          <p class="text-lg font-semibold text-slate-900">{{ executiveSummary.evaluation_type }}</p>
        </div>
      </div>
      <p class="mt-6 text-slate-700 italic">{{ executiveSummary.description }}</p>
    </div>

    <!-- Distribución de Respuestas por Pregunta -->
    <div v-if="hasAnswerDistribution" class="bg-white rounded-xl border border-slate-200 shadow-sm">
      <div class="p-6 border-b border-slate-200">
        <h3 class="text-xl font-bold text-slate-900">Distribución de Respuestas</h3>
        <p class="text-sm text-slate-600 mt-1">
          Porcentaje de respuestas afirmativas (Sí) por pregunta
        </p>
      </div>

      <div class="p-6 space-y-4">
        <div
          v-for="(stats, key) in aggregatedStats.answer_distribution"
          :key="key"
          class="group"
        >
          <div class="flex items-start gap-3 mb-2">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-xs font-bold text-slate-700">
              {{ extractQuestionNumber(key) }}
            </span>
            <p class="text-sm text-slate-700 leading-relaxed flex-1">
              {{ stats.question_text }}
            </p>
          </div>

          <!-- Progress bar -->
          <div class="ml-11 flex items-center gap-4">
            <div class="flex-1 bg-slate-100 rounded-full h-5 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500 ease-out"
                :class="getBarColor(stats.percentage_yes)"
                :style="{ width: stats.percentage_yes + '%' }"
              />
            </div>
            <div class="flex-shrink-0 flex items-center gap-3 text-xs">
              <span class="font-bold" :class="getTextColor(stats.percentage_yes)">
                {{ stats.percentage_yes }}%
              </span>
              <span class="text-slate-400">
                ({{ stats.yes_count }} Sí / {{ stats.no_count }} No)
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Participantes -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
      <div class="p-6 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h3 class="text-xl font-bold text-slate-900">Lista de Participantes</h3>
          <p class="text-sm text-slate-600 mt-1">
            Trabajadores que completaron la evaluación de Referencia I
          </p>
        </div>
        <span v-if="participants.length > 0" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
          {{ participants.length }} registros
        </span>
      </div>

      <div v-if="participants.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                #
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Folio Personal
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Género
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Departamento
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Puesto
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Resp. Sí
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Fecha
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr
              v-for="(participant, index) in participants"
              :key="participant.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                {{ index + 1 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                {{ participant.personal_folio }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                {{ participant.demographics?.gender ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                {{ participant.demographics?.department ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                {{ participant.demographics?.position ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getYesCountBadge(countYesAnswers(participant.answers))"
                >
                  {{ countYesAnswers(participant.answers) }} / 14
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                {{ formatDate(participant.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="mt-4 text-lg font-medium text-slate-900">No hay participantes</p>
        <p class="mt-2 text-sm text-slate-600">
          No se encontraron evaluaciones de Referencia I completadas para este centro de trabajo.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Participant {
  id: string;
  personal_folio: string;
  folio: string;
  evaluation_type: string;
  created_at: string;
  demographics: Record<string, unknown> | null;
  answers: Record<string, unknown>;
  comments_count: number;
}

interface AnswerStat {
  question_text: string;
  yes_count: number;
  no_count: number;
  total_responses: number;
  percentage_yes: number;
}

interface AggregatedStats {
  total_participants: number;
  total_questions: number;
  demographic_distribution: Record<string, Record<string, number>>;
  answer_distribution: Record<string, AnswerStat>;
  questions_config: Record<string, string>;
}

interface ExecutiveSummary {
  total_participants: number;
  evaluation_type: string;
  description: string;
  total_questions: number;
}

const props = defineProps<{
  participants: Participant[];
  aggregatedStats: AggregatedStats;
  executiveSummary: ExecutiveSummary;
}>();

const hasAnswerDistribution = computed(() => {
  return props.aggregatedStats?.answer_distribution
    && Object.keys(props.aggregatedStats.answer_distribution).length > 0;
});

const extractQuestionNumber = (key: string): string => {
  const match = key.match(/\d+/);
  return match ? match[0] : key;
};

const getBarColor = (percentage: number): string => {
  if (percentage >= 50) {
    return 'bg-red-500';
  }
  if (percentage >= 25) {
    return 'bg-amber-500';
  }
  return 'bg-green-500';
};

const getTextColor = (percentage: number): string => {
  if (percentage >= 50) {
    return 'text-red-700';
  }
  if (percentage >= 25) {
    return 'text-amber-700';
  }
  return 'text-green-700';
};

const countYesAnswers = (answers: Record<string, unknown>): number => {
  if (!answers) {
    return 0;
  }
  return Object.values(answers).filter(
    (v) => v === true || v === 'true' || v === 1 || v === '1' || v === 'Sí' || v === 'sí' || v === 'si'
  ).length;
};

const getYesCountBadge = (count: number): string => {
  if (count >= 7) {
    return 'bg-red-100 text-red-800';
  }
  if (count >= 3) {
    return 'bg-amber-100 text-amber-800';
  }
  return 'bg-green-100 text-green-800';
};

const formatDate = (dateString: string): string => {
  if (!dateString) {
    return 'N/A';
  }
  const date = new Date(dateString);
  return date.toLocaleDateString('es-MX', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};
</script>
