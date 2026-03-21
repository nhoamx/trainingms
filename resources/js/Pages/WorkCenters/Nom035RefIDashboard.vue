<template>
  <Dashboard>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
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

        <!-- Etapas Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 sm:p-8">
            <StagesRefITab
              :analysis-data="analysisData"
              :question-statistics="questionStatistics"
              :block-statistics="blockStatistics"
              :ats-panorama-statistics="atsPanoramaStatistics"
              :acontecimiento-participants="acontecimientoParticipants"
              :clinical-assessment-participants="clinicalAssessmentParticipants"
                :organization-id="dashboardData.organization.id"
                :analysis-blocks="analysisBlocks"
                :can-manage-analysis-blocks="canManageAnalysisBlocks"
              :prevention-actions="preventionActions"
              :can-manage-prevention-actions="isAdmin"
              :work-center-id="dashboardData.work_center.id"
            />
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';
import StagesRefITab from '@/Components/Organization/Nom035/StagesRefITab.vue';
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
  analysisData: {
    evaluations: Array<{
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
    }>;
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
    questions: Array<{
      key: string;
      number: number;
      text: string;
      yes_count: number;
      no_count: number;
      total_responses: number;
      yes_percentage: number;
    }>;
    total_evaluations: number;
  };
  blockStatistics: {
    blocks: Array<{
      name: string;
      question_numbers: number[];
      question_count: number;
      yes_count: number;
      no_count: number;
      total_responses: number;
      yes_percentage: number;
    }>;
    total_evaluations: number;
  };
  atsPanoramaStatistics: {
    items: Array<{
      index: number;
      label: string;
      yes_count: number;
      no_count: number;
      total_responses: number;
    }>;
    total_evaluations: number;
    with_traumatic_event_count: number;
    without_traumatic_event_count: number;
  };
  acontecimientoParticipants: {
    participants: Array<{
      id: string;
      personal_folio: string;
      name: string;
      has_any_event: boolean;
      events: Record<string, boolean>;
      demographics: {
        genero: string;
        edad: string;
        estado_civil: string;
        estudios: string;
        puesto: string;
        area: string;
        tipo_puesto: string;
        tipo_contratacion: string;
        tipo_personal: string;
        turno: string;
        rotacion_turnos: string;
        tiempo_puesto_actual: string;
        tiempo_experiencia_laboral_total: string;
      };
    }>;
    total: number;
  };
  clinicalAssessmentParticipants: {
    participants: Array<{
      id: string;
      personal_folio: string;
      name: string;
      has_sections_ii_iii_iv_answers: boolean;
      requires_clinical_assessment: boolean;
      criteria_met: string[];
      sections: {
        ii: {
          label: string;
          yes_count: number;
          answered_count: number;
          threshold: number;
          meets_rule: boolean;
          responses: Array<{ key: string; number: number; text: string; answer: unknown; is_yes: boolean }>;
        };
        iii: {
          label: string;
          yes_count: number;
          answered_count: number;
          threshold: number;
          meets_rule: boolean;
          responses: Array<{ key: string; number: number; text: string; answer: unknown; is_yes: boolean }>;
        };
        iv: {
          label: string;
          yes_count: number;
          answered_count: number;
          threshold: number;
          meets_rule: boolean;
          responses: Array<{ key: string; number: number; text: string; answer: unknown; is_yes: boolean }>;
        };
      };
      demographics: {
        genero: string;
        edad: string;
        estado_civil: string;
        estudios: string;
        puesto: string;
        area: string;
        tipo_puesto: string;
        tipo_contratacion: string;
        tipo_personal: string;
        turno: string;
        rotacion_turnos: string;
        tiempo_puesto_actual: string;
        tiempo_experiencia_laboral_total: string;
      };
    }>;
    total: number;
    requires_clinical_count: number;
  };
  analysisBlocks?: {
    referencia_i: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
    referencia_iii: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
  };
  canManageAnalysisBlocks?: boolean;
  preventionActions?: Array<{
    id: number;
    title: string;
    description: string | null;
    responsible: string | null;
    status: string;
    due_date: string | null;
  }>;
}

const props = withDefaults(defineProps<Props>(), {
  preventionActions: () => [],
  analysisBlocks: () => ({ referencia_i: [], referencia_iii: [] }),
  canManageAnalysisBlocks: false,
});

const page = usePage();

const isAdmin = computed(() => {
  const roles = (page.props.auth as { user?: { roles?: Array<{ name: string }> } })?.user?.roles ?? [];

  return roles.some((role) => role.name === 'admin' || role.name === 'super-admin');
});

const preventionActions = computed(() => props.preventionActions ?? []);
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
