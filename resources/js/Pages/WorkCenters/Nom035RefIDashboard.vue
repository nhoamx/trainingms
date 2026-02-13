<template>
  <Dashboard>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <!-- Logo -->
            <div v-if="dashboardData.organization.logo" class="flex-shrink-0">
              <img
                :src="dashboardData.organization.logo"
                :alt="`${dashboardData.organization.name} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              />
            </div>
            <div v-else class="flex-shrink-0">
              <div>
                <h1 class="text-4xl font-bold text-gray-900">{{ dashboardData.work_center.name }}</h1>
                <p class="mt-2 text-gray-600">NOM-035-STPS-2018 - Referencia I (ATS)</p>
              </div>
            </div>
            <!-- Language Switcher -->
            <div class="sm:ml-auto">
              <LanguageSwitcher />
            </div>
          </div>

          <!-- Breadcrumb -->
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
            <span class="font-medium text-gray-900">Guía de Referencia I (ATS)</span>
          </nav>
        </div>

        <!-- Tabs Navigation -->
        <div class="mb-8">
          <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'px-4 sm:px-6 py-3 sm:py-4 rounded-lg font-semibold text-sm sm:text-base transition-all duration-200',
                activeTab === tab.key
                  ? 'bg-blue-600 text-white shadow-lg hover:bg-blue-700'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900',
              ]"
              :aria-current="activeTab === tab.key ? 'page' : undefined"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 sm:p-8">
            <!-- Empresa -->
            <div v-show="activeTab === 'empresa'" class="animate-fade-in">
              <EmpresaTab :company-data="dashboardData.company_data" :organization="dashboardData.organization" />
            </div>

            <!-- Evaluación -->
            <div v-show="activeTab === 'evaluacion'" class="animate-fade-in">
              <EvaluationRefITab
                :participants="participants"
                :aggregated-stats="aggregatedStats"
                :executive-summary="executiveSummary"
              />
            </div>

            <!-- Análisis -->
            <div v-show="activeTab === 'analisis'" class="animate-fade-in">
              <AnalysisRefITab
                :aggregated-stats="aggregatedStats"
                :participants="participants"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';
import EmpresaTab from '@/Components/Organization/Nom035/EmpresaTab.vue';
import EvaluationRefITab from '@/Components/Organization/Nom035/EvaluationRefITab.vue';
import AnalysisRefITab from '@/Components/Organization/Nom035/AnalysisRefITab.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';

interface WorkCenterInfo {
  id: string;
  name: string;
  code: string;
}

interface CompanyData {
  general: {
    name: string | null;
    razon_social: string | null;
    rfc: string | null;
    registro_patronal: string | null;
    actividad_principal: string | null;
    folio_organization: number | null;
  };
  address: {
    calle_numero: string | null;
    colonia: string | null;
    codigo_postal: string | null;
    municipio: string | null;
    estado: string | null;
  };
  contact: {
    nombre: string | null;
    puesto: string | null;
    email: string | null;
    movil: string | null;
  };
  responsible: {
    nombre: string | null;
    puesto: string | null;
    email: string | null;
    movil: string | null;
  };
  workforce?: {
    total_trabajadores: number | null;
    total_hombres: number | null;
    total_mujeres: number | null;
  };
  sample?: {
    muestra_aplicada: number | null;
    muestra_hombres: number | null;
    muestra_mujeres: number | null;
    justificacion_muestra: string | null;
  };
  evaluation_date?: string | null;
  committee?: {
    comite_integrantes: number | null;
    comite_mujeres: number | null;
    comite_hombres: number | null;
  };
}

interface DashboardData {
  organization: {
    id: string;
    name: string;
    logo: string | null;
  };
  work_center: WorkCenterInfo;
  company_data: CompanyData;
  demographic_summary: Record<string, unknown>;
  demographic_details: Record<string, unknown>;
}

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

interface AggregatedStats {
  total_participants: number;
  total_questions: number;
  demographic_distribution: Record<string, Record<string, number>>;
  answer_distribution: Record<string, unknown>;
  questions_config: Record<string, string>;
}

interface ExecutiveSummary {
  total_participants: number;
  evaluation_type: string;
  description: string;
  total_questions: number;
}

interface Props {
  dashboardData: DashboardData;
  aggregatedStats: AggregatedStats;
  participants: Participant[];
  executiveSummary: ExecutiveSummary;
}

defineProps<Props>();

const tabs = [
  { key: 'empresa', label: 'Empresa' },
  { key: 'evaluacion', label: 'Evaluación' },
  { key: 'analisis', label: 'Análisis' },
];

const activeTab = ref<string>('empresa');
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-in;
}
</style>
