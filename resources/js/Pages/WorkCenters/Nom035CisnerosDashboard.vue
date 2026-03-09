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
              />
            </div>

            <div class="flex-shrink-0">
              <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ dashboardData.work_center.name }}</h1>
              <p class="mt-2 text-gray-600">NOM-035-STPS-2018 - Escala Cisneros</p>
            </div>

            <div class="sm:ml-auto">
              <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Evaluaciones Cisneros</p>
                <p class="text-2xl font-bold text-orange-900">{{ cisnerosEvaluationsCount }}</p>
              </div>
            </div>
          </div>

          <nav class="flex items-center text-sm text-gray-500 mt-2" aria-label="Breadcrumb">
            <Link
              :href="route('my-work-centers')"
              class="hover:text-blue-600 transition-colors"
            >
              Mis Centros de Trabajo
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <Link
              :href="route('work-centers.dashboard.nom-035-index', dashboardData.work_center.id)"
              class="hover:text-blue-600 transition-colors"
            >
              {{ dashboardData.work_center.name }}
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-gray-900">Escala Cisneros</span>
          </nav>
        </div>

        <div class="space-y-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Evaluaciones</p>
              <p class="mt-1 text-2xl font-bold text-orange-900">{{ cisnerosSummary.total_evaluations }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Victima (SI)</p>
              <p class="mt-1 text-2xl font-bold text-emerald-900">{{ cisnerosSummary.victim_yes }}</p>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Victima (NO)</p>
              <p class="mt-1 text-2xl font-bold text-sky-900">{{ cisnerosSummary.victim_no }}</p>
            </div>
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Tasa SI</p>
              <p class="mt-1 text-2xl font-bold text-violet-900">{{ cisnerosSummary.victim_yes_percentage }}%</p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <CisnerosDistributionCharts
              title="Autores (A, B, C)"
              description="Distribucion por tipo de persona involucrada."
              :items="authorsChart"
            />
            <CisnerosDistributionCharts
              title="Frecuencia (0-6)"
              description="Distribucion de frecuencia reportada en las respuestas."
              :items="frequencyChart"
            />
          </div>

          <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-bold text-gray-900">Tabla de respuestas por pregunta</h2>
              <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                {{ responsesTable.length }} respuestas
              </span>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Folio</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Pregunta</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Autor</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Frecuencia</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Victima ultimos 6 meses</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="row in responsesTable" :key="`${row.folio}-${row.question_number}`" class="hover:bg-gray-50">
                    <td class="whitespace-nowrap px-3 py-2 font-medium text-gray-900">{{ row.folio }}</td>
                    <td class="px-3 py-2 text-gray-700">
                      <span class="font-semibold">{{ row.question_number }}.</span>
                      {{ row.question_text }}
                    </td>
                    <td class="px-3 py-2 text-gray-700">{{ row.author_code ? `${row.author_code} - ${row.author_label}` : 'Sin respuesta' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ row.frequency_value !== null ? `${row.frequency_value} - ${row.frequency_label}` : 'Sin respuesta' }}</td>
                    <td class="px-3 py-2">
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
                  <tr v-if="responsesTable.length === 0">
                    <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">
                      No hay respuestas de Escala Cisneros para mostrar en este centro de trabajo.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';
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

defineProps<{
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
  responsesTable: Array<{
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
  }>;
}>();

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);
</script>
