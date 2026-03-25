<template>
  <Dashboard>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <div v-if="organization.logo" class="flex-shrink-0">
              <img
                :src="organization.logo"
                :alt="`${organization.name} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              />
            </div>
            <div class="flex-shrink-0">
              <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ workCenter.name }}</h1>
              <p class="mt-2 text-gray-600">Clima Laboral</p>
            </div>
          </div>

          <!-- Breadcrumb -->
          <nav class="flex items-center text-sm text-gray-500 mt-2" aria-label="Breadcrumb">
            <Link
              :href="route('my-work-centers')"
              class="hover:text-teal-600 transition-colors"
            >
              Mis Centros de Trabajo
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <Link
              :href="route('work-centers.dashboard.nom-035-index', workCenter.id)"
              class="hover:text-teal-600 transition-colors"
            >
              {{ workCenter.name }}
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-gray-900">Clima Laboral</span>
          </nav>
        </div>

        <!-- Summary Banner -->
        <div class="bg-gradient-to-r from-teal-600 to-cyan-700 rounded-2xl p-8 mb-8 text-white shadow-lg">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h2 class="text-2xl font-bold">Dashboard Clima Laboral</h2>
              <p class="mt-1 text-teal-100">
                Análisis de ambiente de trabajo, liderazgo y bienestar organizacional
              </p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
              <p class="text-3xl font-bold">{{ totalEvaluations }}</p>
              <p class="text-xs text-teal-100 mt-0.5">Participantes</p>
            </div>
          </div>
        </div>

        <!-- Results -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
          <div class="p-6 sm:p-8">
            <ClimaLaboralResultsTab
              :evaluations="evaluations"
              :organization-id="organization.id"
            />
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Dashboard from '@/Layouts/Dashboard.vue';
import ClimaLaboralResultsTab from '@/Components/Organization/ClimaLaboralResultsTab.vue';

interface WorkCenterInfo {
  id: string;
  name: string;
  code: string;
}

interface OrganizationInfo {
  id: string;
  name: string;
  logo: string | null;
}

interface DemographicData {
  gender?: string;
  contract_type?: string;
  position?: string;
  department?: string;
  work_schedule?: string;
}

interface Evaluation {
  id: string;
  folio: string;
  personal_folio: string;
  total_score: number;
  interpretation: string;
  demographicData?: DemographicData;
}

defineProps<{
  workCenter: WorkCenterInfo;
  organization: OrganizationInfo;
  evaluations: Evaluation[];
  totalEvaluations: number;
}>();

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);
</script>
