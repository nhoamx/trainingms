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

        <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm">
          <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-700">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3.944a11.955 11.955 0 01-8.618 2.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.59 21 9c0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">Dashboard de Escala Cisneros</h2>
              <p class="mt-1 text-sm text-gray-600">
                Esta vista ya está habilitada y será extendida con indicadores de violencia laboral, análisis por área y seguimiento de casos prioritarios.
              </p>
            </div>
          </div>

          <div
            class="mt-6 rounded-xl border p-4"
            :class="cisnerosEvaluationsCount > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50'"
          >
            <p class="text-sm font-semibold" :class="cisnerosEvaluationsCount > 0 ? 'text-emerald-800' : 'text-gray-700'">
              {{ cisnerosEvaluationsCount > 0 ? 'Hay evaluaciones disponibles para análisis.' : 'No hay evaluaciones de Cisneros procesadas para este centro de trabajo.' }}
            </p>
            <p class="mt-1 text-sm text-gray-600">
              {{ cisnerosEvaluationsCount > 0 ? 'Siguiente paso: incorporar resumen ejecutivo, factores críticos y plan de acción.' : 'Cuando se carguen evaluaciones, aquí verás el resumen del instrumento y sus etapas.' }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';

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
}>();

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);
</script>
