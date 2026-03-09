<template>
  <Dashboard>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <div v-if="dashboardData.organization.logo" class="flex-shrink-0">
              <img
                :src="dashboardData.organization.logo"
                :alt="`${dashboardData.organization.name} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              >
            </div>

            <div class="flex-shrink-0">
              <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ dashboardData.work_center.name }}</h1>
              <p class="mt-2 text-gray-600">NOM-035-STPS-2018 - Escala Cisneros</p>
            </div>

            <div class="sm:ml-auto">
              <LanguageSwitcher />
            </div>
          </div>

          <nav class="mt-2 flex items-center text-sm text-gray-500" aria-label="Breadcrumb">
            <Link :href="route('my-work-centers')" class="hover:text-blue-600 transition-colors">
              Mis Centros de Trabajo
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <Link :href="route('work-centers.dashboard.nom-035-index', dashboardData.work_center.id)" class="hover:text-blue-600 transition-colors">
              {{ dashboardData.work_center.name }}
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-gray-900">Escala Cisneros</span>
          </nav>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
          <div class="border-b border-gray-200 px-6 pt-6 pb-5">
            <h2 class="text-2xl font-bold text-gray-900">Panel de Riesgo de Violencia Laboral</h2>
            <p class="mt-1 text-sm text-gray-600">Resumen de resultados y matriz analitica por reactivo.</p>
          </div>

          <div class="border-b border-gray-200 px-6 pt-4 pb-4">
            <nav class="flex flex-wrap gap-2" aria-label="Tabs">
              <button
                v-for="tab in viewTabs"
                :key="tab.key"
                @click="activeView = tab.key"
                :class="[
                  'rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors',
                  activeView === tab.key ? 'bg-orange-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                ]"
              >
                {{ tab.label }}
              </button>
            </nav>
          </div>

          <div class="space-y-8 p-6 pb-10 sm:p-8 sm:pb-12">
            <div v-if="activeView === 'executive'" class="space-y-8">
              <div class="space-y-6">
                <CisnerosDistributionCharts
                  title="Distribucion de autores"
                  description="Participacion relativa por tipo de persona involucrada (A, B, C)."
                  :items="authorsChart"
                />
                <CisnerosDistributionCharts
                  title="Distribucion de frecuencia"
                  description="Comportamiento de respuestas segun escala de frecuencia (0 a 6)."
                  :items="frequencyChart"
                />
              </div>

              <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3 sm:px-5">
                  <h3 class="text-base font-bold text-gray-900">Participantes evaluados</h3>
                  <p class="mt-1 text-sm text-gray-600">Folio, puesto, departamento y sexo con acceso a detalle de respuestas.</p>
                </div>

                <div class="overflow-auto">
                  <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                      <tr>
                        <th class="px-4 py-2.5 text-left font-semibold">Folio evaluacion</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Folio participante</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Puesto</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Departamento</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Sexo</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Victima 6 meses</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                      <tr v-if="participants.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                          No hay participantes para mostrar.
                        </td>
                      </tr>
                      <tr v-for="participant in participants" :key="participant.id" class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-medium text-gray-900">{{ participant.folio }}</td>
                        <td class="px-4 py-2.5 text-gray-700">{{ participant.personal_folio }}</td>
                        <td class="px-4 py-2.5 text-gray-700">{{ participant.position }}</td>
                        <td class="px-4 py-2.5 text-gray-700">{{ participant.department }}</td>
                        <td class="px-4 py-2.5 text-gray-700">{{ participant.gender }}</td>
                        <td class="px-4 py-2.5">
                          <span
                            class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                            :class="
                              participant.victim_last_6_months === true
                                ? 'bg-emerald-100 text-emerald-800'
                                : participant.victim_last_6_months === false
                                  ? 'bg-slate-100 text-slate-700'
                                  : 'bg-amber-100 text-amber-800'
                            "
                          >
                            {{ participant.victim_last_6_months_label }}
                          </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                          <button
                            type="button"
                            class="rounded-md bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-500"
                            @click="openParticipantDetail(participant.folio)"
                          >
                            Ver detalles
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div v-if="activeView === 'matrix'" class="space-y-5">
              <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                <div>
                  <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Buscar pregunta</label>
                  <input
                    v-model="questionSearch"
                    type="text"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                    placeholder="Ej. descalificar, ignorar, gritar"
                  >
                </div>
                <div>
                  <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Filtro victima</label>
                  <select
                    v-model="victimFilter"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                  >
                    <option value="all">Todos</option>
                    <option value="yes">Con SI</option>
                    <option value="no">Solo NO</option>
                    <option value="unknown">Con indefinidos</option>
                  </select>
                </div>
                <div class="flex items-end">
                  <p class="text-sm text-gray-600">
                    Mostrando <span class="font-semibold text-gray-900">{{ filteredQuestionSummary.length }}</span>
                    de <span class="font-semibold text-gray-900">{{ questionSummary.length }}</span> reactivos.
                  </p>
                </div>
              </div>

              <div class="overflow-auto rounded-xl border border-gray-200">
                <table class="min-w-[1160px] w-full text-sm">
                  <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700">
                    <tr>
                      <th class="px-3 py-3 text-left font-semibold">Reactivo</th>
                      <th class="px-3 py-3 text-center font-semibold">Total</th>
                      <th class="px-3 py-3 text-center font-semibold">A</th>
                      <th class="px-3 py-3 text-center font-semibold">B</th>
                      <th class="px-3 py-3 text-center font-semibold">C</th>
                      <th v-for="frequency in frequencyScale" :key="`freq_${frequency}`" class="px-3 py-3 text-center font-semibold">
                        F{{ frequency }}
                      </th>
                      <th class="px-3 py-3 text-center font-semibold">SI</th>
                      <th class="px-3 py-3 text-center font-semibold">NO</th>
                      <th class="px-3 py-3 text-center font-semibold">N/D</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-if="filteredQuestionSummary.length === 0">
                      <td colspan="14" class="px-4 py-8 text-center text-gray-500">
                        No hay reactivos para los filtros seleccionados.
                      </td>
                    </tr>
                    <tr v-for="item in filteredQuestionSummary" :key="`q_${item.questionNumber}`" class="hover:bg-gray-50">
                      <td class="px-3 py-2.5 text-gray-800">
                        <p class="font-semibold">{{ item.questionNumber }}. {{ item.questionText }}</p>
                      </td>
                      <td class="px-3 py-2.5 text-center font-semibold text-gray-900">{{ item.total }}</td>
                      <td class="px-3 py-2.5 text-center font-semibold" :class="intensityClass(item.authors.A, authorMax, 'amber')">{{ item.authors.A }}</td>
                      <td class="px-3 py-2.5 text-center font-semibold" :class="intensityClass(item.authors.B, authorMax, 'amber')">{{ item.authors.B }}</td>
                      <td class="px-3 py-2.5 text-center font-semibold" :class="intensityClass(item.authors.C, authorMax, 'amber')">{{ item.authors.C }}</td>
                      <td
                        v-for="frequency in frequencyScale"
                        :key="`q_${item.questionNumber}_${frequency}`"
                        class="px-3 py-2.5 text-center font-semibold"
                        :class="intensityClass(item.frequencies[frequency], frequencyMax, 'sky')"
                      >
                        {{ item.frequencies[frequency] }}
                      </td>
                      <td class="px-3 py-2.5 text-center font-semibold" :class="intensityClass(item.victim.yes, victimMax, 'emerald')">{{ item.victim.yes }}</td>
                      <td class="px-3 py-2.5 text-center font-semibold" :class="intensityClass(item.victim.no, victimMax, 'slate')">{{ item.victim.no }}</td>
                      <td class="px-3 py-2.5 text-center font-semibold" :class="intensityClass(item.victim.unknown, victimMax, 'amber')">{{ item.victim.unknown }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="selectedParticipant"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeParticipantDetail"
    >
      <div class="max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-xl">
        <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Detalle de participante</h3>
            <p class="mt-1 text-sm text-gray-600">
              Folio evaluacion: {{ selectedParticipant.folio }} | Folio participante: {{ selectedParticipant.personal_folio }}
            </p>
          </div>
          <button type="button" class="rounded-md px-2 py-1 text-gray-500 hover:bg-gray-100" @click="closeParticipantDetail">Cerrar</button>
        </div>

        <div class="max-h-[78vh] space-y-4 overflow-auto px-5 py-4">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-xs uppercase tracking-wide text-gray-500">Puesto</p>
              <p class="text-sm font-semibold text-gray-900">{{ selectedParticipant.position }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-xs uppercase tracking-wide text-gray-500">Departamento</p>
              <p class="text-sm font-semibold text-gray-900">{{ selectedParticipant.department }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-xs uppercase tracking-wide text-gray-500">Sexo</p>
              <p class="text-sm font-semibold text-gray-900">{{ selectedParticipant.gender }}</p>
            </div>
          </div>

          <div class="overflow-auto rounded-lg border border-gray-200">
            <table class="min-w-[900px] w-full text-sm">
              <thead class="bg-gray-50 text-gray-700">
                <tr>
                  <th class="px-3 py-2.5 text-left font-semibold">Pregunta</th>
                  <th class="px-3 py-2.5 text-left font-semibold">Autor</th>
                  <th class="px-3 py-2.5 text-left font-semibold">Frecuencia</th>
                  <th class="px-3 py-2.5 text-left font-semibold">Victima ultimos 6 meses</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-if="selectedParticipantRows.length === 0">
                  <td colspan="4" class="px-3 py-8 text-center text-gray-500">Sin respuestas disponibles para este participante.</td>
                </tr>
                <tr v-for="row in selectedParticipantRows" :key="`${row.folio}-${row.question_number}`">
                  <td class="px-3 py-2.5 text-gray-800">
                    <span class="font-semibold">{{ row.question_number }}.</span>
                    {{ row.question_text }}
                  </td>
                  <td class="px-3 py-2.5 text-gray-700">{{ row.author_code ? `${row.author_code} - ${row.author_label}` : 'Sin respuesta' }}</td>
                  <td class="px-3 py-2.5 text-gray-700">{{ row.frequency_value !== null ? `${row.frequency_value} - ${row.frequency_label}` : 'Sin respuesta' }}</td>
                  <td class="px-3 py-2.5">
                    <span
                      class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                      :class="
                        row.victim_last_6_months === true
                          ? 'bg-emerald-100 text-emerald-800'
                          : row.victim_last_6_months === false
                            ? 'bg-slate-100 text-slate-700'
                            : 'bg-amber-100 text-amber-800'
                      "
                    >
                      {{ row.victim_last_6_months_label }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import CisnerosDistributionCharts from '@/Components/Organization/Nom035/Charts/CisnerosDistributionCharts.vue';

interface WorkCenterInfo {
  id: string;
  name: string;
  code: string;
}

interface DashboardData {
  organization: {
    id: string;
    name: string;
    logo: string | null;
  };
  work_center: WorkCenterInfo;
}

interface ResponseRow {
  folio: string;
  personal_folio: string;
  question_number: number;
  question_text: string;
  author_code: string | null;
  author_label: string | null;
  frequency_value: number | null;
  frequency_label: string | null;
  victim_last_6_months: boolean | null;
  victim_last_6_months_label: string;
}

interface ParticipantRow {
  id: string;
  folio: string;
  personal_folio: string;
  gender: string;
  position: string;
  department: string;
  victim_last_6_months: boolean | null;
  victim_last_6_months_label: string;
  answered_questions: number;
}

const props = defineProps<{
  dashboardData: DashboardData;
  cisnerosEvaluationsCount: number;
  cisnerosSummary: {
    total_evaluations: number;
    victim_yes: number;
    victim_no: number;
    victim_unknown: number;
    victim_yes_percentage: number;
  };
  authorsChart: Array<{
    key: string;
    label: string;
    count: number;
    color: string;
  }>;
  frequencyChart: Array<{
    key: string;
    label: string;
    count: number;
    color: string;
  }>;
  participants: ParticipantRow[];
  responsesTable: ResponseRow[];
}>();

const viewTabs = [
  { key: 'executive', label: 'Vista ejecutiva' },
  { key: 'matrix', label: 'Matriz por reactivo' },
] as const;

const activeView = ref<(typeof viewTabs)[number]['key']>('executive');
const questionSearch = ref('');
const victimFilter = ref<'all' | 'yes' | 'no' | 'unknown'>('all');
const selectedParticipantFolio = ref<string | null>(null);

const frequencyScale = [0, 1, 2, 3, 4, 5, 6] as const;

interface QuestionSummary {
  questionNumber: number;
  questionText: string;
  total: number;
  authors: Record<'A' | 'B' | 'C', number>;
  frequencies: Record<number, number>;
  victim: {
    yes: number;
    no: number;
    unknown: number;
  };
}

const questionSummary = computed<QuestionSummary[]>(() => {
  const grouped = new Map<number, QuestionSummary>();

  for (const row of props.responsesTable) {
    if (!grouped.has(row.question_number)) {
      grouped.set(row.question_number, {
        questionNumber: row.question_number,
        questionText: row.question_text,
        total: 0,
        authors: { A: 0, B: 0, C: 0 },
        frequencies: { 0: 0, 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 },
        victim: { yes: 0, no: 0, unknown: 0 },
      });
    }

    const item = grouped.get(row.question_number);
    if (!item) {
      continue;
    }

    item.total += 1;

    const normalizedAuthor = row.author_code ? row.author_code.toUpperCase() : null;
    if (normalizedAuthor === 'A' || normalizedAuthor === 'B' || normalizedAuthor === 'C') {
      item.authors[normalizedAuthor] += 1;
    }

    if (row.frequency_value !== null && frequencyScale.includes(row.frequency_value as (typeof frequencyScale)[number])) {
      item.frequencies[row.frequency_value] += 1;
    }

    if (row.victim_last_6_months === true) {
      item.victim.yes += 1;
    } else if (row.victim_last_6_months === false) {
      item.victim.no += 1;
    } else {
      item.victim.unknown += 1;
    }
  }

  return Array.from(grouped.values()).sort((a, b) => a.questionNumber - b.questionNumber);
});

const filteredQuestionSummary = computed<QuestionSummary[]>(() => {
  const search = questionSearch.value.trim().toLowerCase();

  return questionSummary.value.filter((item) => {
    const matchesSearch =
      search.length === 0 ||
      item.questionText.toLowerCase().includes(search) ||
      String(item.questionNumber).includes(search);

    if (!matchesSearch) {
      return false;
    }

    if (victimFilter.value === 'yes') {
      return item.victim.yes > 0;
    }

    if (victimFilter.value === 'no') {
      return item.victim.yes === 0 && item.victim.no > 0;
    }

    if (victimFilter.value === 'unknown') {
      return item.victim.unknown > 0;
    }

    return true;
  });
});

const selectedParticipant = computed(() => {
  if (selectedParticipantFolio.value === null) {
    return null;
  }

  return props.participants.find((participant) => participant.folio === selectedParticipantFolio.value) ?? null;
});

const selectedParticipantRows = computed(() => {
  if (selectedParticipantFolio.value === null) {
    return [];
  }

  return props.responsesTable
    .filter((row) => row.folio === selectedParticipantFolio.value)
    .sort((left, right) => left.question_number - right.question_number);
});

const openParticipantDetail = (folio: string): void => {
  selectedParticipantFolio.value = folio;
};

const closeParticipantDetail = (): void => {
  selectedParticipantFolio.value = null;
};

const authorMax = computed(() => {
  return Math.max(1, ...questionSummary.value.map((item) => Math.max(item.authors.A, item.authors.B, item.authors.C)));
});

const frequencyMax = computed(() => {
  return Math.max(1, ...questionSummary.value.flatMap((item) => frequencyScale.map((value) => item.frequencies[value])));
});

const victimMax = computed(() => {
  return Math.max(1, ...questionSummary.value.map((item) => Math.max(item.victim.yes, item.victim.no, item.victim.unknown)));
});

const intensityClass = (count: number, maxCount: number, palette: 'amber' | 'sky' | 'emerald' | 'slate'): string => {
  const ratio = maxCount > 0 ? count / maxCount : 0;

  if (count === 0) {
    return 'text-gray-500 bg-white';
  }

  if (palette === 'amber') {
    return ratio > 0.66 ? 'bg-amber-300 text-amber-900' : ratio > 0.33 ? 'bg-amber-200 text-amber-900' : 'bg-amber-100 text-amber-800';
  }

  if (palette === 'sky') {
    return ratio > 0.66 ? 'bg-sky-300 text-sky-900' : ratio > 0.33 ? 'bg-sky-200 text-sky-900' : 'bg-sky-100 text-sky-800';
  }

  if (palette === 'emerald') {
    return ratio > 0.66 ? 'bg-emerald-300 text-emerald-900' : ratio > 0.33 ? 'bg-emerald-200 text-emerald-900' : 'bg-emerald-100 text-emerald-800';
  }

  return ratio > 0.66 ? 'bg-slate-300 text-slate-900' : ratio > 0.33 ? 'bg-slate-200 text-slate-900' : 'bg-slate-100 text-slate-700';
};

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);
</script>
