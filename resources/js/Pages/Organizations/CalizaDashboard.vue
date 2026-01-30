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
                <h1 class="text-4xl font-bold text-gray-900">{{ dashboardData.organization.name }}</h1>
                <p class="mt-2 text-gray-600">NOM-035-STPS-2018</p>
              </div>
            </div>
            <!-- Language Switcher -->
            <div class="sm:ml-auto">
              <LanguageSwitcher />
            </div>
          </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="mb-8">
          <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
            <button
              v-for="tab in translatedTabs"
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

            <!-- Comité -->
            <div v-show="activeTab === 'comite'" class="animate-fade-in">
              <CommitteeTab :company-data="dashboardData.company_data" />
            </div>

            <!-- Sensibilización -->
            <div v-show="activeTab === 'sensibilizacion'" class="animate-fade-in">
              <SensibilizationTab />
            </div>

            <!-- Política -->
            <div v-show="activeTab === 'politica'" class="animate-fade-in">
              <PolicyTab />
            </div>

            <!-- Evaluación -->
            <div v-show="activeTab === 'evaluacion'" class="animate-fade-in">
              <EvaluationTab 
                :evaluations="evaluations"
                :available-evaluation-types="props.availableEvaluationTypes"
              />
            </div>

            <!-- Etapas -->
            <div v-show="activeTab === 'etapas'" class="animate-fade-in">
              <StagesTab 
                :domain-statistics="props.domainStatistics" 
                :category-statistics="props.categoryStatistics"
                :dimension-statistics="props.dimensionStatistics"
                :question-statistics="props.questionStatistics"
                :block-statistics="props.blockStatistics"
                :global-statistics="props.globalStatistics"
                :analysis-data="props.analysisData"
                :organization-id="props.dashboardData.organization.id"
              />
            </div>

            <!-- Referencia -->
            <div v-show="activeTab === 'referencia'" class="animate-fade-in">
              <ReferenceTab />
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Dashboard from '../../Layouts/Dashboard.vue';
import EmpresaTab from '@/Components/Organization/Nom035/EmpresaTab.vue';
import CommitteeTab from '@/Components/Organization/Nom035/CommitteeTab.vue';
import SensibilizationTab from '@/Components/Organization/Nom035/SensibilizationTab.vue';
import PolicyTab from '@/Components/Organization/Nom035/PolicyTab.vue';
import EvaluationTab from '@/Components/Organization/Nom035/EvaluationTab.vue';
import StagesTab from '@/Components/Organization/Nom035/StagesTab.vue';
import ReferenceTab from '@/Components/Organization/Nom035/ReferenceTab.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

interface Tab {
  key: string;
  labelKey: string;
}

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  departments: string[];
  work_schedules: string[];
  total_evaluations: number;
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
  committee?: {
    comite_integrantes: number | null;
    comite_mujeres: number | null;
    comite_hombres: number | null;
  };
  evaluation_date?: string | null;
}

interface DashboardData {
  organization: {
    id: string;
    name: string;
    logo: string | null;
  };
  company_data: CompanyData;
  demographic_summary: Record<string, unknown>;
  demographic_details: DemographicDetails;
}

interface DomainStatistics {
  domains: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface CategoryStatistics {
  categories: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface DimensionStatistics {
  dimensions: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface GlobalStatistics {
  global: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface AnalysisData {
  evaluations: Array<{
    id: string;
    folio: string;
    personal_folio: string;
    evaluee_name: string;
    demographics: {
      genero: string;
      puesto: string;
      area: string;
      turno: string;
    };
    domain_scores: Record<string, { score: number; risk_level: string }>;
    category_scores: Record<string, { score: number; risk_level: string; domain: string }>;
  }>;
  demographics: {
    generos: string[];
    puestos: string[];
    areas: string[];
    turnos: string[];
  };
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface Evaluation {
  id: string;
  evaluation_type?: string;
  personal_folio?: string;
  demographicData?: Record<string, unknown>;
}

interface EvaluationType {
  key: string;
  label: string;
  description: string;
  badge: string;
  color: string;
  icon: string;
}

interface Props {
  dashboardData: DashboardData;
  domainStatistics?: DomainStatistics;
  categoryStatistics?: CategoryStatistics;
  dimensionStatistics?: DimensionStatistics;
  questionStatistics?: { questions: Record<string, unknown>; total_evaluations: number };
  blockStatistics?: { blocks: Record<string, unknown>; total_evaluations: number };
  globalStatistics?: GlobalStatistics;
  analysisData?: AnalysisData;
  evaluations?: Evaluation[];
  availableEvaluationTypes?: EvaluationType[];
}

const props = withDefaults(defineProps<Props>(), {
  domainStatistics: () => ({ domains: {}, total_evaluations: 0, colors: {}, labels: {} }),
  categoryStatistics: () => ({ categories: {}, total_evaluations: 0, colors: {}, labels: {} }),
  dimensionStatistics: () => ({ dimensions: {}, total_evaluations: 0, colors: {}, labels: {} }),
  globalStatistics: () => ({ global: {}, total_evaluations: 0, colors: {}, labels: {} }),
  analysisData: () => ({ evaluations: [], demographics: { generos: [], puestos: [], areas: [], turnos: [] }, colors: {}, labels: {} }),
  evaluations: () => [],
  availableEvaluationTypes: () => [],
});

const tabs: Tab[] = [
  { key: 'empresa', labelKey: 'Empresa' },
  { key: 'comite', labelKey: 'Comité' },
  { key: 'sensibilizacion', labelKey: 'Sensibilización' },
  { key: 'politica', labelKey: 'Política' },
  { key: 'evaluacion', labelKey: 'Evaluación' },
  { key: 'etapas', labelKey: 'Etapas' },
  { key: 'referencia', labelKey: 'Referencia' },
];

const translatedTabs = computed(() =>
  tabs.map(tab => ({
    key: tab.key,
    label: t(tab.labelKey),
  }))
);

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
